import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

cmd = "ls -la public_html/website_7e9e6396/admin"
print(f"--- Checking {cmd} ---")
stdin, stdout, stderr = client.exec_command(cmd)
print(stdout.read().decode())

client.close()
