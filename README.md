# Weight Tracker App

## Overview
The **Weight Tracker App** is a simple web application built with PHP, HTML, and JavaScript to help users log, visualize, and manage their weight data over time. It includes interactive features for tracking weight progress and managing entries.

---

## Features

### Logging Weight:
- Users can enter their weight (in kilograms) and an optional date.
- If no date is provided, the current date is automatically used.

### Visualizing Progress:
- Weight data is displayed in an interactive line chart using [Chart.js](https://www.chartjs.org/).
- The chart updates dynamically to reflect changes in the data.

### Managing Entries:
- Stored weight data is saved in a JSON file (`weights.json`).
- Users can delete entries directly from the chart by clicking on a data point.

### Responsive Design:
- The app works on both desktop and mobile devices, ensuring usability across platforms.

---

## Installation

### Prerequisites:
- A web server with PHP support (e.g., Apache, Nginx).
- PHP 7.4 or higher.

### Steps:
1. Clone or download the repository to your web server's root directory:
   ```bash
   git clone https://github.com/yourusername/weight-tracker.git
   ```

2. Ensure the server has write permissions to the `weights.json` file or the directory containing it.

3. Access the app through your browser by navigating to the server URL, e.g., `http://localhost/weight-tracker`.

---

## Usage

### Logging Weight:
1. Open the app in your browser.
2. Enter your weight in the provided field.
3. Optionally, select a date.
4. Click "Submit" to save the entry.

### Viewing Progress:
- Weight entries are displayed in a line chart.
- Hover over data points to view details.

### Deleting Entries:
1. Click on a data point in the chart.
2. The corresponding entry is removed, and the chart updates automatically.

---

## API Endpoints

### Get Weight Data:
- **Endpoint:** `?action=get`
- **Method:** GET
- **Response:** JSON array of weight entries.

### Delete Weight Entry:
- **Endpoint:** `?action=delete&datetime=<date>`
- **Method:** GET
- **Parameters:**
  - `datetime` (required): The date of the entry to delete.
- **Response:** JSON success message.

---

## File Structure
```
weight-tracker/
├── weights.json       # Data storage for weight entries
├── index.php          # Main application logic
├── style.css          # Styles for the app
└── weight.png         # App icon
```

---

## Dependencies
- [Chart.js](https://www.chartjs.org/) for rendering the interactive chart.

---

## License
This project is open source and available under the [MIT License](LICENSE).

---

## Acknowledgments
Special thanks to the creators of [Chart.js](https://www.chartjs.org/) for providing an excellent charting library.

---
