import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

# List only directories in public_html to see the structure
cmd = "ls -d public_html/*/ | xargs -n 1 basename"

stdin, stdout, stderr = client.exec_command(cmd)
print(stdout.read().decode())
client.close()
