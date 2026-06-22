import os
import subprocess

base_dir = r"C:\Users\Ing. Joan Sanchez\Documents\GitHub\jaryelm.github.io\MEDIDATA_Lab_Serv"
errors_found = 0

for root, dirs, files in os.walk(base_dir):
    for file in files:
        if file.endswith(".php"):
            filepath = os.path.join(root, file)
            result = subprocess.run(["php", "-l", filepath], capture_output=True, text=True)
            if result.returncode != 0:
                print(f"Error in {filepath}:")
                print(result.stdout)
                print(result.stderr)
                print("-" * 40)
                errors_found += 1

if errors_found == 0:
    print("All PHP files passed linting successfully!")
else:
    print(f"Total files with errors: {errors_found}")
