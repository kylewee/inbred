import paramiko
import os

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

local_file = "hostgator_restore.php"
remote_path = "public_html/tmp/hostgator_restore.php"

try:
    transport = paramiko.Transport((host, port))
    transport.connect(username=user, password=password)
    sftp = paramiko.SFTPClient.from_transport(transport)
    
    if sftp:
        print(f"Uploading {local_file} to {remote_path}...")
        sftp.put(local_file, remote_path)
        print("Upload successful.")
        sftp.close()
    
    transport.close()
except Exception as e:
    print(f"Upload failed: {e}")
