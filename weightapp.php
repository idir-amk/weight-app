<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DATA_FILE', 'weights.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = isset($_POST['weight']) && $_POST['weight'] != '' ? (float)$_POST['weight'] : 0;
    $cals = isset($_POST['cals']) ? (int)$_POST['cals'] : 0;
    $steps = isset($_POST['steps']) ? (int)$_POST['steps'] : 0;
    $date = isset($_POST['date']) && !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');
    $datetime = $date;

    $data = file_exists(DATA_FILE) ? json_decode(file_get_contents(DATA_FILE), true) : [];

    if ($weight == 0) {
        $weight = predictWeight($steps, $cals, $data);
    }

    $data[] = [
        "datetime" => $datetime,
        "weight" => $weight,
        "cals" => $cals,
        "steps" => $steps
    ];

    usort($data, function ($a, $b) {
        return strtotime($a['datetime']) - strtotime($b['datetime']);
    });

    file_put_contents(DATA_FILE, json_encode($data));

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get') {
    header('Content-Type: application/json');
    if (file_exists(DATA_FILE)) {
        echo file_get_contents(DATA_FILE);
    } else {
        echo json_encode([]);
    }
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    header('Content-Type: application/json');

    if (!isset($_GET['datetime'])) {
        echo json_encode(['success' => false, 'message' => 'Missing datetime parameter']);
        exit;
    }

    $datetime = $_GET['datetime'];
    $data = file_exists(DATA_FILE) ? json_decode(file_get_contents(DATA_FILE), true) : [];

    if (empty($data)) {
        echo json_encode(['success' => false, 'message' => 'No data found to delete']);
        exit;
    }

    $data = array_filter($data, function ($entry) use ($datetime) {
        return isset($entry['datetime']) && $entry['datetime'] !== $datetime;
    });

    $data = array_values($data);
    file_put_contents(DATA_FILE, json_encode($data));

    echo json_encode(['success' => true]);
    exit;
}

function predictWeight($steps, $cals, $previousData) {
    $caloriesPerKg = 7700;
    $stepsPerKg = 10000;

    $lastKnownWeight = $previousData[count($previousData) - 1]['weight'];

    $totalCals = 0;
    $totalSteps = 0;
    $totalWeightChange = 0;
    $numDays = count($previousData) - 1;

    for ($i = 1; $i < count($previousData); $i++) {
        $currentWeight = $previousData[$i]['weight'];
        $previousWeight = $previousData[$i - 1]['weight'];

        $weightChange = $previousWeight - $currentWeight;

        $totalCals += $previousData[$i]['cals'];
        $totalSteps += $previousData[$i]['steps'];
        $totalWeightChange += $weightChange;
    }

    $avgCals = $totalCals / $numDays;
    $avgSteps = $totalSteps / $numDays;

    $caloricDifference = $cals - $avgCals;

    $stepsDifference = $steps - $avgSteps;

    $predictedWeightChangeFromCals = ($caloricDifference / $caloriesPerKg);

    $predictedWeightChangeFromSteps = ($stepsDifference / $stepsPerKg);

    $predictedWeight = $lastKnownWeight + $predictedWeightChangeFromCals - $predictedWeightChangeFromSteps;

    return round($predictedWeight, 2);
}

function renderHtmlPage($message = '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <link rel="stylesheet" href="style.css">
        <link rel="icon" href="weight.png">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Weight Tracker</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
    document.addEventListener("DOMContentLoaded", function () {
        fetch("<?php echo $_SERVER['PHP_SELF']; ?>?action=get")
            .then(response => response.json())
            .then(data => {
                const ctx = document.getElementById("weightChart").getContext("2d");
                const labels = data.map(entry => entry.datetime);
                const weights = data.map(entry => entry.weight);

                const chart = new Chart(ctx, {
                    type: "line",
                    data: {
                        labels: labels,
                        datasets: [{
                            label: "Weight",
                            data: weights,
                            segment: {
                                borderColor: function (context) {
                                    const weight = context.p1.raw;
                                    const prevWeight = context.p0.raw;

                                    if (weight >= 77 && weight <= 80) {
                                        return "green";
                                    } else if (prevWeight !== undefined) {
                                        const movingTowardGoal = 
                                            (prevWeight > 80 && weight < prevWeight) ||
                                            (prevWeight < 77 && weight > prevWeight);
                                        return movingTowardGoal ? "orange" : "red";
                                    } else {
                                        return "red";
                                    }
                                }
                            },
                            borderWidth: 2,
                            fill: false,
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const index = context.dataIndex;
                                        const entry = data[index];
                                        return [
                                            `Weight: ${entry.weight} kg`,
                                            `Calories: ${entry.cals || 0} kcal`,
                                            `Steps: ${entry.steps || 0}`
                                        ];
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { title: { display: true, text: "Date" } },
                            y: { title: { display: true, text: "Weight (kg)" } }
                        },
                        onClick: function (evt) {
                            const activePoint = chart.getElementsAtEventForMode(evt, "nearest", { intersect: true }, true)[0];
                            if (activePoint) {
                                const index = activePoint.index;
                                const datetime = chart.data.labels[index];
                                fetch("<?php echo $_SERVER['PHP_SELF']; ?>?action=delete&datetime=" + encodeURIComponent(datetime))
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            chart.data.labels.splice(index, 1);
                                            chart.data.datasets[0].data.splice(index, 1);
                                            chart.update();
                                        }
                                    });
                            }
                        }
                    }
                });
            });
    });
</script>
    </head>
    <body>
        <div class="flex">
            <h1>Weight Tracker</h1>
            <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                <label class="app" for="weight">Enter your weight:</label><br>
                <input type="number" id="weight" name="weight" step="0.1"><br>
                <label class="app" for="cals">How much Kcals Yesterday?</label><br>
                <input type="number" id="cals" name="cals" step="100"><br>
                <label class="app" for="steps">How much Steps Yesterday?</label><br>
                <input type="number" id="steps" name="steps" step="100"><br>
                <label class="app" for="date">Enter date (optional):</label><br>
                <input type="date" id="date" name="date"><br>

                <input type="submit" value="Submit">
            </form>

            <?php
            if (isset($_POST['steps']) && isset($_POST['cals'])) {
                $steps = $_POST['steps'];
                $cals = $_POST['cals'];
                $data = file_exists(DATA_FILE) ? json_decode(file_get_contents(DATA_FILE), true) : [];
                if ($weight == 0) {
                    $predictedWeight = predictWeight($steps, $cals, $data);
                    echo "<p>Predicted weight: $predictedWeight kg</p>";
                }
            }
            ?>
        </div>
        <canvas id="weightChart"></canvas>
    </body>
    </html>
    <?php
}

renderHtmlPage();
?>
