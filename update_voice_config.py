import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)
sftp = client.open_sftp()

# 1. Upload updated incoming.php
local_path = "voice/incoming.php"
remote_path = "public_html/website_7e9e6396/voice/incoming.php"
print(f"Uploading {local_path}...")
sftp.put(local_path, remote_path)

# 2. Create voice.log
log_path = "public_html/website_7e9e6396/voice/voice.log"
print(f"Creating {log_path}...")
try:
    with sftp.open(log_path, 'w') as f:
        f.write("")
    sftp.chmod(log_path, 0o666) # Read/Write for everyone
    print("Log created successfully.")
except Exception as e:
    print(f"Log creation failed: {e}")

sftp.close()
client.close()
