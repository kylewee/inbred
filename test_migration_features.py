import paramiko
import requests
import time

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

# 1. Check for ffmpeg and PHP modules
client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
try:
    client.connect(host, port=port, username=user, password=password)
    
    print("--- Checking Server Capabilities ---")
    commands = [
        "which ffmpeg",
        "php -m | grep curl",
        "php -v",
        "ls -la public_html/website_7e9e6396/voice/voice.log" # Check log permissions
    ]
    
    for cmd in commands:
        print(f"Cmd: {cmd}")
        stdin, stdout, stderr = client.exec_command(cmd)
        print(stdout.read().decode().strip())
        
    client.close()
except Exception as e:
    print(f"SSH failed: {e}")

# 2. Test Web Quote API (Simulated)
print("\n--- Testing Web Quote API ---")
url = "http://mechanicstaugustine.com/api/quote_intake.php"
# Fallback to direct IP if DNS isn't ready
ip_url = "http://162.144.1.99/api/quote_intake.php"
headers = {"Host": "mechanicstaugustine.com"}

data = {
    "name": "Test User Migration",
    "phone": "9045550000",
    "email": "test@migration.com",
    "vehicle_year": "2020",
    "vehicle_make": "Ford",
    "vehicle_model": "F-150",
    "issue": "Migration Test Quote"
}

try:
    # Try domain first
    print(f"POSTing to {url}...")
    r = requests.post(url, json=data, timeout=5)
    print(f"Status: {r.status_code}")
    print(f"Response: {r.text[:200]}")
except Exception as e:
    print(f"Domain failed ({e}), trying direct IP...")
    try:
        r = requests.post(ip_url, json=data, headers=headers, timeout=5)
        print(f"Status: {r.status_code}")
        print(f"Response: {r.text[:200]}")
    except Exception as e2:
        print(f"Direct IP failed: {e2}")
