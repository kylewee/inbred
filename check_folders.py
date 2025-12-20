import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

# Check folders and permissions
cmd = "ls -la public_html/website_7e9e6396"
print(f"--- Listing {cmd} ---")
stdin, stdout, stderr = client.exec_command(cmd)
print(stdout.read().decode())

# Check .htaccess in crm if it exists
cmd = "cat public_html/website_7e9e6396/crm/.htaccess"
print(f"\n--- Reading CRM .htaccess ---")
stdin, stdout, stderr = client.exec_command(cmd)
print(stdout.read().decode())
print(stderr.read().decode())

client.close()
