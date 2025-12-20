import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)
sftp = client.open_sftp()

local_path = "extract_rukovoditel.php"
remote_path = "extract_rukovoditel.php"

print(f"Uploading {local_path}...")
sftp.put(local_path, remote_path)
print("Upload complete.")

# Run the PHP script
cmd = "php extract_rukovoditel.php"
print(f"Running: {cmd}")
stdin, stdout, stderr = client.exec_command(cmd)
out = stdout.read().decode()
err = stderr.read().decode()
if out: print("Output:", out)
if err: print("Error:", err)

sftp.close()
client.close()