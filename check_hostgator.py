import paramiko
import sys

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"

def try_connect(port):
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        print(f"Connecting to {host}:{port}...")
        client.connect(host, port=port, username=user, password=password)
        print(f"Connected successfully on port {port}.")
        return client
    except Exception as e:
        print(f"Failed to connect on port {port}: {e}")
        return None

client = try_connect(2222)
if not client:
    client = try_connect(22)

if client:
    try:
        commands = [
            "ls -la public_html/tmp",
            "ls -la public_html"
        ]
        
        for cmd in commands:
            print(f"\nExecuting: {cmd}")
            stdin, stdout, stderr = client.exec_command(cmd)
            print(stdout.read().decode())
            err = stderr.read().decode()
            if err:
                print(f"Error: {err}")
    finally:
        client.close()
else:
    print("Could not connect to HostGator on port 2222 or 22.")
