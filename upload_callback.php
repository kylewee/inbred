import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)
sftp = client.open_sftp()

local_path = "voice/recording_callback.php"
remote_path = "public_html/website_7e9e6396/voice/recording_callback.php"

print(f"Uploading {local_path}...")
sftp.put(local_path, remote_path)
print("Upload complete.")

sftp.close()
client.close()
