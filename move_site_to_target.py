import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

# 1. Ensure target exists (it should, based on previous list)
# 2. Move contents of 'mechanic' into 'website_7e9e6396'
# 3. Clean up 'mechanic' folder

target = "public_html/website_7e9e6396"
source = "public_html/mechanic"

commands = [
    f"mkdir -p {target}",
    # Move contents. using rsync is safer if available, but mv is standard.
    # We use cp -r then rm to be safe against partial moves, or just mv.
    # "mv" is instant on same filesystem.
    f"cp -r {source}/. {target}/",
    f"rm -rf {source}",
    f"ls -la {target} | head -n 10"
]

print(f"Moving site from {source} to {target}...")
for cmd in commands:
    print(f"\nRunning: {cmd}")
    stdin, stdout, stderr = client.exec_command(cmd)
    out = stdout.read().decode()
    err = stderr.read().decode()
    if out: print(out)
    if err: print(f"Error/Warning: {err}")

client.close()
