import paramiko
import os

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

files_to_sync = [
    ("voice/incoming.php", "public_html/mechanic/voice/incoming.php"),
    ("voice/recording_callback.php", "public_html/mechanic/voice/recording_callback.php"),
    ("api/.env.local.php", "public_html/mechanic/api/.env.local.php")
]

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    if sftp:
        for local, remote in files_to_sync:
            if os.path.exists(local):
                print(f"Uploading {local} -> {remote}...")
                sftp.put(local, remote)
            else:
                print(f"Warning: Local file {local} not found!")
        
        print("Sync complete.")
        sftp.close()
    
    transport.close()
except Exception as e:
    print(f"Sync failed: {e}")
