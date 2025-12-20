import paramiko

host = "gator2117.hostgator.com"
user = "cpunccte"
password = "4299BbCtgw5hcsCh"
port = 2222

db_user = "cpunccte_kylewee"
db_pass = "R0ckS0l!dR0ck"
db_name = "cpunccte_mechanic"

# Query to list all configuration items that might look like URLs
sql = "SELECT * FROM app_configuration WHERE configuration_value LIKE '%http%' OR configuration_name LIKE '%url%';"

cmd = f"mysql -u {db_user} -p'{db_pass}' {db_name} -e \"{sql}\""

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(host, port=port, username=user, password=password)

print(f"--- Checking CRM Configuration ---")
# Suppress password warning
stdin, stdout, stderr = client.exec_command(cmd)
out = stdout.read().decode()
err = stderr.read().decode()

if out:
    print(out)
else:
    print("No URL configurations found (or query returned empty).")

if err and "Using a password" not in err:
    print(f"Error: {err}")

client.close()
