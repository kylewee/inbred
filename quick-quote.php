<!DOCTYPE html>
<html>
<head>
    <title>Quick Quote</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin: 0 0 20px 0;
            color: #333;
        }
        label {
            display: block;
            margin: 15px 0 5px 0;
            font-weight: bold;
            color: #555;
        }
        input, select {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 15px;
            margin-top: 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #1d4ed8;
        }
        #result {
            margin-top: 30px;
            padding: 20px;
            background: #10b981;
            color: white;
            border-radius: 10px;
            display: none;
        }
        #result h2 {
            margin: 0 0 15px 0;
        }
        #result .price {
            font-size: 36px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
        }
        .small {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Get a Quick Quote</h1>

        <label>Year</label>
        <input type="number" id="year" placeholder="2004" value="2004">

        <label>Make</label>
        <input type="text" id="make" placeholder="BMW" value="BMW">

        <label>Model</label>
        <input type="text" id="model" placeholder="330xi" value="330xi">

        <label>What Needs Fixed?</label>
        <select id="repair">
            <option value="">-- Select Repair --</option>
            <option value="Oil Change|0.5">Oil Change (30 min)</option>
            <option value="Battery|0.5">Battery (30 min)</option>
            <option value="Brake Pads|1.5">Brake Pads (1.5 hrs)</option>
            <option value="Power Steering Hose|1.0">Power Steering Hose (1 hr)</option>
            <option value="Power Steering Pump|1.5">Power Steering Pump (1.5 hrs)</option>
            <option value="Alternator|2.0">Alternator (2 hrs)</option>
            <option value="Starter|1.5">Starter (1.5 hrs)</option>
            <option value="Water Pump|2.5">Water Pump (2.5 hrs)</option>
            <option value="Timing Belt|3.0">Timing Belt (3 hrs)</option>
            <option value="Spark Plugs|1.0">Spark Plugs 4-cyl (1 hr)</option>
            <option value="Spark Plugs V6|1.5">Spark Plugs V6 (1.5 hrs)</option>
            <option value="Spark Plugs V8|2.0">Spark Plugs V8 (2 hrs)</option>
            <option value="AC Recharge|0.8">AC Recharge (45 min)</option>
            <option value="Serpentine Belt|0.8">Serpentine Belt (45 min)</option>
        </select>

        <button onclick="calculate()">GET MY PRICE</button>

        <div id="result">
            <h2 id="resultTitle"></h2>
            <div id="resultDetails"></div>
            <div class="price" id="resultPrice"></div>
            <div class="small" style="text-align: center;">Call 904-217-5152 to book!</div>
        </div>
    </div>

    <script>
        function calculate() {
            const year = document.getElementById('year').value;
            const make = document.getElementById('make').value;
            const model = document.getElementById('model').value;
            const repairSelect = document.getElementById('repair');
            const repairValue = repairSelect.value;

            // Validate
            if (!year || !make || !model) {
                alert('Please enter your vehicle year, make, and model');
                return;
            }

            if (!repairValue) {
                alert('Please select what needs to be fixed');
                return;
            }

            // Parse repair
            const [repairName, hoursStr] = repairValue.split('|');
            const hours = parseFloat(hoursStr);

            // Calculate labor ($150 first hour, $100 each additional hour)
            let price = 0;
            if (hours <= 1.0) {
                price = hours * 150;
            } else {
                price = 150 + ((hours - 1.0) * 100);
            }

            // BMW premium
            const isBMW = make.toLowerCase().includes('bmw') ||
                         make.toLowerCase().includes('mercedes') ||
                         make.toLowerCase().includes('audi');
            const premium = isBMW ? ' (BMW/Luxury)' : '';

            // Show result
            document.getElementById('resultTitle').textContent =
                year + ' ' + make + ' ' + model + premium;

            document.getElementById('resultDetails').innerHTML =
                '<div class="small">' + repairName + '</div>' +
                '<div class="small">' + hours + ' hour' + (hours !== 1 ? 's' : '') + ' of labor</div>' +
                '<div class="small">$150 first hour, then $100/hr</div>';

            document.getElementById('resultPrice').textContent = '$' + price.toFixed(2);

            document.getElementById('result').style.display = 'block';
            document.getElementById('result').scrollIntoView({behavior: 'smooth'});
        }
    </script>
</body>
</html>
