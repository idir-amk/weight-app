<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DATA_FILE', 'weights.json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['weight'])) {
        renderHtmlPage("Error: Missing weight parameter.");
        exit;
    }

    $weight = (float)$_POST['weight'];
    $date = isset($_POST['date']) && !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d');

    // Only store the date without time
    $datetime = $date;

    $data = file_exists(DATA_FILE) ? json_decode(file_get_contents(DATA_FILE), true) : [];

    $data[] = ["datetime" => $datetime, "weight" => $weight];

    // Sort data by datetime
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
                                    borderColor: "white",
                                    borderWidth: 2,
                                    fill: false,
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    legend: { display: false }
                                },
                                scales: {
                                    x: { title: { display: true, text: "Date" } },
                                    y: { title: { display: true, text: "Weight (kg)" } }
                                },
                                onClick: function(evt) {
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
                <input type="number" id="weight" name="weight" step="0.1" required><br>
                <label class="app" for="date">Enter date (optional):</label><br>
                <input type="date" id="date" name="date"><br>

                <input type="submit" value="Submit">
            </form>
        </div>
            <canvas id="weightChart"></canvas>
    </body>
    </html>
    <?php
}

renderHtmlPage();
?>
