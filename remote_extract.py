import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

# 1. Create target directory
# 2. Extract tar.gz file
commands = [
    "mkdir -p public_html/mechanic",
    "tar -xzf public_html/tmp/backup_site_20251211_060322.tar.gz -C public_html/mechanic",
    "ls -la public_html/mechanic | head -n 10"
]

print("Starting extraction...")
for cmd in commands:
    print(f"\nRunning: {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode()
    err = stderr.read().decode()
    if out: print(out)
    if err: print(f"Error/Warning: {err}")

client.close()
