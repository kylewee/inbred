#!/usr/bin/env python3
"""
Setup SignalWire AI Agent for St. Augustine Mobile Mechanic
Creates the Sarah agent and configures SWAIG functions
"""

import requests
import json
import sys

# Configuration
SPACE_URL = "mobilemechanic.signalwire.com"
PROJECT_ID = "ce4806cb-ccb0-41e9-8bf1-7ea59536adfd"
API_TOKEN = "PT1c8cf22d1446d4d9daaf580a26ad92729e48a4a33beb769a"
PHONE_NUMBER = "+19042175152"
WEBHOOK_ENDPOINT = "https://mechanicstaugustine.com/voice/swaig_functions.php"

# API Base URL
API_BASE = f"https://{SPACE_URL}/api/fabric/v1"

# Headers for API requests
HEADERS = {
    "Authorization": f"Bearer {API_TOKEN}",
    "Content-Type": "application/json"
}

def log(message, level="INFO"):
    """Print formatted log message"""
    print(f"[{level}] {message}")

def create_agent():
    """Create the Sarah AI Agent"""
    log("Creating AI Agent 'Sarah'...")

    payload = {
        "name": "Sarah",
        "language": "en-US",
        "system_prompt": """You are Sarah, a friendly and professional virtual assistant for St. Augustine Mobile Mechanic. You help customers when the mechanic can't answer, gathering their information and providing instant price estimates when possible.

You're helpful, conversational, and understand that people calling often have car trouble and may be stressed. Keep the conversation natural - don't sound like a form.

IMPORTANT RULES:
1. Always get the customer's full name (first and last)
2. Always get vehicle details (year, make, model)
3. Always ask what's wrong with their vehicle
4. Before offering an estimate, confirm you have: vehicle year, make, model, and problem description
5. Use the get_estimate function to provide pricing
6. When creating a lead, always include the estimated price in the notes field
7. Be conversational and natural - avoid sounding robotic""",
        "initial_message": "Hi, you've reached St. Augustine Mobile Mechanic. I'm Sarah, the virtual assistant. The mechanic is with another customer right now, but I can help you. What's your name?",
        "enable_voice": True,
        "voice": "google-us-en-female-c"  # Google English US Female
    }

    try:
        response = requests.post(
            f"{API_BASE}/resources/ai_agents",
            headers=HEADERS,
            json=payload,
            timeout=10
        )

        if response.status_code in [200, 201]:
            agent_data = response.json()
            agent_id = agent_data.get('data', {}).get('id') or agent_data.get('id')
            log(f"✓ Agent created successfully! ID: {agent_id}")
            return agent_id
        else:
            log(f"✗ Failed to create agent: {response.status_code}", "ERROR")
            log(f"Response: {response.text}", "ERROR")
            return None
    except Exception as e:
        log(f"✗ Error creating agent: {str(e)}", "ERROR")
        return None

def add_swaig_function(agent_id, function_name, description, parameters):
    """Add a SWAIG function to the agent"""
    log(f"Adding function '{function_name}' to agent...")

    payload = {
        "name": function_name,
        "description": description,
        "endpoint": WEBHOOK_ENDPOINT,
        "parameters": parameters
    }

    try:
        response = requests.post(
            f"{API_BASE}/resources/ai_agents/{agent_id}/functions",
            headers=HEADERS,
            json=payload,
            timeout=10
        )

        if response.status_code in [200, 201]:
            log(f"✓ Function '{function_name}' added successfully")
            return True
        else:
            log(f"✗ Failed to add function: {response.status_code}", "ERROR")
            log(f"Response: {response.text}", "ERROR")
            return False
    except Exception as e:
        log(f"✗ Error adding function: {str(e)}", "ERROR")
        return False

def setup_functions(agent_id):
    """Setup both SWAIG functions"""
    log("Setting up SWAIG functions...")

    # Function 1: get_estimate
    get_estimate_params = {
        "year": {
            "type": "string",
            "description": "Vehicle year (e.g., 2018)"
        },
        "make": {
            "type": "string",
            "description": "Vehicle make (e.g., Honda)"
        },
        "model": {
            "type": "string",
            "description": "Vehicle model (e.g., Accord)"
        },
        "problem": {
            "type": "string",
            "description": "What's wrong with the vehicle"
        }
    }

    success1 = add_swaig_function(
        agent_id,
        "get_estimate",
        "Get price estimate for a vehicle repair based on year, make, model, and problem description",
        get_estimate_params
    )

    # Function 2: create_lead
    create_lead_params = {
        "first_name": {
            "type": "string",
            "description": "Customer's first name"
        },
        "last_name": {
            "type": "string",
            "description": "Customer's last name"
        },
        "phone": {
            "type": "string",
            "description": "Customer's phone number"
        },
        "year": {
            "type": "string",
            "description": "Vehicle year"
        },
        "make": {
            "type": "string",
            "description": "Vehicle make"
        },
        "model": {
            "type": "string",
            "description": "Vehicle model"
        },
        "notes": {
            "type": "string",
            "description": "Additional notes (include estimated price)"
        }
    }

    success2 = add_swaig_function(
        agent_id,
        "create_lead",
        "Save customer information to the CRM for mechanic follow-up",
        create_lead_params
    )

    return success1 and success2

def connect_phone_number(agent_id):
    """Connect phone number to the agent"""
    log(f"Connecting phone number {PHONE_NUMBER} to agent...")

    payload = {
        "ai_agent_id": agent_id,
        "phone_number": PHONE_NUMBER
    }

    try:
        # First, get the phone number resource
        response = requests.get(
            f"{API_BASE}/resources/phone_numbers?phone_number={PHONE_NUMBER}",
            headers=HEADERS,
            timeout=10
        )

        if response.status_code != 200:
            log(f"✗ Could not find phone number: {response.status_code}", "ERROR")
            return False

        phone_data = response.json()
        phone_id = phone_data.get('data', [{}])[0].get('id')

        if not phone_id:
            log("✗ Phone number ID not found", "ERROR")
            return False

        # Update phone number to route to agent
        update_payload = {
            "ai_agent_id": agent_id
        }

        response = requests.patch(
            f"{API_BASE}/resources/phone_numbers/{phone_id}",
            headers=HEADERS,
            json=update_payload,
            timeout=10
        )

        if response.status_code in [200, 204]:
            log(f"✓ Phone number {PHONE_NUMBER} connected to agent")
            return True
        else:
            log(f"✗ Failed to connect phone number: {response.status_code}", "ERROR")
            log(f"Response: {response.text}", "ERROR")
            return False
    except Exception as e:
        log(f"✗ Error connecting phone number: {str(e)}", "ERROR")
        return False

def main():
    """Main setup process"""
    log("=" * 60)
    log("SignalWire AI Agent Setup - Sarah for St. Augustine Mobile Mechanic")
    log("=" * 60)
    log(f"Space: {SPACE_URL}")
    log(f"Project ID: {PROJECT_ID}")
    log(f"Phone Number: {PHONE_NUMBER}")
    log("")

    # Step 1: Create agent
    agent_id = create_agent()
    if not agent_id:
        log("Failed to create agent. Aborting.", "ERROR")
        sys.exit(1)

    log("")

    # Step 2: Setup functions
    if not setup_functions(agent_id):
        log("Warning: Some functions failed to setup", "WARNING")

    log("")

    # Step 3: Connect phone number
    if not connect_phone_number(agent_id):
        log("Warning: Phone number connection failed", "WARNING")

    log("")
    log("=" * 60)
    log("✓ Setup Complete!")
    log("=" * 60)
    log(f"Agent ID: {agent_id}")
    log(f"Agent Name: Sarah")
    log(f"Phone Number: {PHONE_NUMBER}")
    log("")
    log("Next steps:")
    log("1. Verify the agent in your SignalWire dashboard")
    log("2. Test with a call to the phone number")
    log("3. Monitor logs at: /home/kylewee/code/inbred/voice/voice.log")
    log("")

if __name__ == "__main__":
    main()
