#!/usr/bin/env python3
"""
Mobile Mechanic Call Handling System
Based on Kyle's ChatGPT conversation requirements

Features:
- Natural speech call intake (no ID tags)
- Urgency scale 1-5 (DTMF input)
- AI transcription and data extraction
- SMS confirmation with edit links
- Quick roadside customer forms
- CRM integration (Rukovoditel)
"""

from flask import Flask, request, jsonify, render_template, redirect, url_for
from twilio.rest import Client
from twilio.twiml.voice_response import VoiceResponse
import openai
import os
import json
import mysql.connector
from datetime import datetime
import uuid
import logging
from urllib.parse import urlencode

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = Flask(__name__)

# Configuration from environment
TWILIO_ACCOUNT_SID = os.getenv('TWILIO_ACCOUNT_SID')
TWILIO_AUTH_TOKEN = os.getenv('TWILIO_AUTH_TOKEN')
TWILIO_PHONE_NUMBER = os.getenv('TWILIO_PHONE_NUMBER', '+19047066669')
OPENAI_API_KEY = os.getenv('OPENAI_API_KEY')
DB_CONFIG = {
    'host': os.getenv('DB_HOST', 'localhost'),
    'database': os.getenv('DB_NAME', 'mechanic_crm'),
    'user': os.getenv('DB_USER', 'root'),
    'password': os.getenv('DB_PASSWORD', '')
}

# Initialize clients
twilio_client = Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN) if TWILIO_ACCOUNT_SID else None
openai.api_key = OPENAI_API_KEY

@app.route('/health')
def health_check():
    """Health check endpoint"""
    return jsonify({"status": "operational", "service": "mobile-mechanic-calls"})

@app.route('/voice/webhook', methods=['POST'])
def handle_incoming_call():
    """
    Main voice webhook - starts natural conversation intake
    """
    logger.info(f"Incoming call: {dict(request.form)}")
    
    call_sid = request.form.get('CallSid')
    from_number = request.form.get('From')
    
    # Create call record
    call_id = create_call_record(call_sid, from_number)
    
    # Generate TwiML for natural conversation
    response = VoiceResponse()
    response.say(
        "Thank you for calling Mechanic Saint Augustine! I'll need to collect "
        "a few details to provide you with a quote. Let's start with your full name.",
        voice='alice'
    )
    
    # Record the conversation for AI processing
    response.record(
        action=f'/voice/process-intake?call_id={call_id}',
        max_length=300,  # 5 minutes max
        play_beep=False,
        transcribe=True
    )
    
    return str(response), 200, {'Content-Type': 'application/xml'}

@app.route('/voice/process-intake', methods=['POST'])
def process_intake_recording():
    """
    Process the recorded conversation using AI to extract customer data
    """
    call_id = request.args.get('call_id')
    recording_url = request.form.get('RecordingUrl')
    transcription_text = request.form.get('TranscriptionText', '')
    
    logger.info(f"Processing intake for call {call_id}: {recording_url}")
    
    if transcription_text:
        # Extract customer data using AI
        customer_data = extract_customer_data_from_transcription(transcription_text)
        
        # Save to CRM
        if customer_data:
            save_customer_to_crm(customer_data, call_id)
            
            # Present urgency scale
            response = VoiceResponse()
            response.say(
                "Great! Now, on a scale of 1 to 5, please press a number to indicate "
                "how urgent this service is. Press 1 for just an estimate with no rush, "
                "up to 5 if you need immediate assistance.",
                voice='alice'
            )
            
            response.gather(
                action=f'/voice/urgency?call_id={call_id}',
                method='POST',
                num_digits=1,
                timeout=10
            )
            
            return str(response), 200, {'Content-Type': 'application/xml'}
    
    # Fallback if transcription failed
    response = VoiceResponse()
    response.say(
        "I'm having trouble processing that. Let me transfer you to our mechanic.",
        voice='alice'
    )
    response.dial('+19046634789')  # Kyle's personal cell
    
    return str(response), 200, {'Content-Type': 'application/xml'}

@app.route('/voice/urgency', methods=['POST'])
def handle_urgency_rating():
    """
    Handle urgency scale input (1-5)
    """
    call_id = request.args.get('call_id')
    digits = request.form.get('Digits')
    
    urgency = int(digits) if digits and digits.isdigit() and 1 <= int(digits) <= 5 else 3
    
    # Update CRM with urgency
    update_urgency_in_crm(call_id, urgency)
    
    # Send SMS confirmation
    customer_phone = get_customer_phone_from_call(call_id)
    if customer_phone:
        send_sms_confirmation(customer_phone, call_id)
    
    # Final response
    response = VoiceResponse()
    if urgency >= 4:
        response.say(
            "Thank you! We've marked this as high priority. You'll receive a text "
            "shortly to review your information, and we'll contact you today.",
            voice='alice'
        )
    else:
        response.say(
            "Perfect! You'll receive a text message shortly where you can review "
            "and edit your information. We'll get back to you with a quote soon.",
            voice='alice'
        )
    
    response.hangup()
    return str(response), 200, {'Content-Type': 'application/xml'}

def extract_customer_data_from_transcription(transcription):
    """
    Use OpenAI to extract structured data from natural conversation
    """
    try:
        prompt = f"""
        Extract customer information from this mobile mechanic service call transcription.
        Return ONLY a JSON object with these fields (use null if not mentioned):
        
        {{
            "first_name": "string",
            "last_name": "string", 
            "phone": "string",
            "address": "string",
            "vehicle_year": "string",
            "vehicle_make": "string", 
            "vehicle_model": "string",
            "engine_size": "string",
            "problem_description": "string"
        }}
        
        Transcription: {transcription}
        """
        
        response = openai.ChatCompletion.create(
            model="gpt-3.5-turbo",
            messages=[{"role": "user", "content": prompt}],
            temperature=0.1
        )
        
        result = response.choices[0].message.content.strip()
        return json.loads(result)
    
    except Exception as e:
        logger.error(f"AI extraction failed: {e}")
        return None

@app.route('/customer/form/<call_id>')
def customer_review_form(call_id):
    """
    Customer form to review and edit their information
    """
    customer_data = get_customer_data_from_crm(call_id)
    return render_template('customer_form.html', 
                         customer=customer_data, 
                         call_id=call_id)

@app.route('/customer/update/<call_id>', methods=['POST'])
def update_customer_info(call_id):
    """
    Handle customer form submission
    """
    form_data = request.form.to_dict()
    update_customer_in_crm(call_id, form_data)
    
    return render_template('confirmation.html', 
                         message="Your information has been updated successfully!")

@app.route('/new')
def quick_customer_form():
    """
    Quick roadside customer intake form
    Short URL: yourdomain.com/new
    """
    return render_template('quick_intake.html')

@app.route('/quick-submit', methods=['POST'])
def quick_submit():
    """
    Handle quick roadside form submission
    """
    form_data = request.form.to_dict()
    form_data['source'] = 'roadside_form'
    form_data['urgency'] = form_data.get('urgency', '3')
    
    # Create new customer record
    call_id = str(uuid.uuid4())
    save_customer_to_crm(form_data, call_id)
    
    return render_template('confirmation.html',
                         message="Information received! We'll contact you shortly.")

@app.route('/trust')
def trust_page():
    """
    Page explaining why customers can trust Kyle as their mechanic
    """
    return render_template('trust.html')

@app.route('/pricing')
def pricing_page():
    """
    Page explaining how mobile mechanic shops charge
    """
    return render_template('pricing.html')

# Database/CRM Functions
def create_call_record(call_sid, from_number):
    """Create initial call record in CRM"""
    call_id = str(uuid.uuid4())
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        query = """
        INSERT INTO customer_calls (call_id, call_sid, phone_number, created_at, status)
        VALUES (%s, %s, %s, %s, 'in_progress')
        """
        cursor.execute(query, (call_id, call_sid, from_number, datetime.now()))
        conn.commit()
        
    except Exception as e:
        logger.error(f"Database error: {e}")
    finally:
        if 'conn' in locals():
            conn.close()
    
    return call_id

def save_customer_to_crm(customer_data, call_id):
    """Save extracted customer data to CRM"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        query = """
        UPDATE customer_calls SET 
        first_name=%s, last_name=%s, address=%s, vehicle_year=%s,
        vehicle_make=%s, vehicle_model=%s, engine_size=%s, 
        problem_description=%s, status='data_collected'
        WHERE call_id=%s
        """
        
        cursor.execute(query, (
            customer_data.get('first_name'),
            customer_data.get('last_name'), 
            customer_data.get('address'),
            customer_data.get('vehicle_year'),
            customer_data.get('vehicle_make'),
            customer_data.get('vehicle_model'),
            customer_data.get('engine_size'),
            customer_data.get('problem_description'),
            call_id
        ))
        conn.commit()
        
    except Exception as e:
        logger.error(f"CRM save error: {e}")
    finally:
        if 'conn' in locals():
            conn.close()

def send_sms_confirmation(phone_number, call_id):
    """Send SMS with link to review information"""
    if not twilio_client:
        return
        
    message_body = (
        f"Thanks for calling Mechanic St. Augustine! "
        f"Review your info here: https://yourdomain.com/customer/form/{call_id}"
    )
    
    try:
        twilio_client.messages.create(
            body=message_body,
            from_=TWILIO_PHONE_NUMBER,
            to=phone_number
        )
        logger.info(f"SMS sent to {phone_number}")
    except Exception as e:
        logger.error(f"SMS failed: {e}")

def get_customer_phone_from_call(call_id):
    """Get customer phone number from call record"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute("SELECT phone_number FROM customer_calls WHERE call_id=%s", (call_id,))
        result = cursor.fetchone()
        return result[0] if result else None
    except Exception as e:
        logger.error(f"Database error: {e}")
        return None
    finally:
        if 'conn' in locals():
            conn.close()

def update_urgency_in_crm(call_id, urgency):
    """Update urgency rating in CRM"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        cursor.execute(
            "UPDATE customer_calls SET urgency=%s WHERE call_id=%s",
            (urgency, call_id)
        )
        conn.commit()
    except Exception as e:
        logger.error(f"Urgency update error: {e}")
    finally:
        if 'conn' in locals():
            conn.close()

def get_customer_data_from_crm(call_id):
    """Retrieve customer data for form display"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT * FROM customer_calls WHERE call_id=%s", (call_id,))
        return cursor.fetchone()
    except Exception as e:
        logger.error(f"Data retrieval error: {e}")
        return {}
    finally:
        if 'conn' in locals():
            conn.close()

def update_customer_in_crm(call_id, form_data):
    """Update customer data from form submission"""
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()
        
        update_fields = []
        values = []
        
        for field, value in form_data.items():
            if field != 'call_id' and value:
                update_fields.append(f"{field}=%s")
                values.append(value)
        
        if update_fields:
            query = f"UPDATE customer_calls SET {', '.join(update_fields)} WHERE call_id=%s"
            values.append(call_id)
            cursor.execute(query, values)
            conn.commit()
            
    except Exception as e:
        logger.error(f"Update error: {e}")
    finally:
        if 'conn' in locals():
            conn.close()

if __name__ == '__main__':
    logger.info("Starting Mobile Mechanic Call Handler...")
    app.run(debug=True, port=5000, host='0.0.0.0')