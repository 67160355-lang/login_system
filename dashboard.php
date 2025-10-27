<?php
// dashboard.php
// Simple Sales Dashboard (Chart.js + Bootstrap) using mysqli (no PDO)
// Refactored for clarity, BS5 grid, and DRY-er JS.

// เปิดการรายงานข้อผิดพลาดของ mysqli เป็น Exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$DB_HOST = 'localhost';
$DB_USER = 's67160355';
$DB_PASS = 'NevSKRAM';
$DB_NAME = 's67160355';

$data = [
  'monthly' => [],
  'category' => [],
  'region' => [],
  'topProducts' => [],
  'payment' => [],
  'hourly' => [],
  'newReturning' => [],
  'kpi' => ['sales_30d' => 0, 'qty_30d' => 0, 'buyers_30d' => 0],
  'error' => null
];

try {
  // เชื่อมต่อฐานข้อมูล
  $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
  $mysqli->set_charset('utf8mb4');

  // Helper function
  function fetch_all($mysqli, $sql) {
    $res = $mysqli->query($sql);
    $rows = [];
    while ($row = $res->fetch_assoc()) {
      $rows[] = $row;
    }
    $res->free();
    return $rows;
  }

  // เตรียมข้อมูลสำหรับกราฟต่าง ๆ
  $data['monthly'] = fetch_all($mysqli, "SELECT ym, net_sales FROM v_monthly_sales");
  $data['category'] = fetch_all($mysqli, "SELECT category, net_sales FROM v_sales_by_category");
  $data['region'] = fetch_all($mysqli, "SELECT region, net_sales FROM v_sales_by_region");
  $data['topProducts'] = fetch_all($mysqli, "SELECT product_name, qty_sold, net_sales FROM v_top_products");
  $data['payment'] = fetch_all($mysqli, "SELECT payment_method, net_sales FROM v_payment_share");
  $data['hourly'] = fetch_all($mysqli, "SELECT hour_of_day, net_sales FROM v_hourly_sales");
  $data['newReturning'] = fetch_all($mysqli, "SELECT date_key, new_customer_sales, returning_sales FROM v_new_vs_returning ORDER BY date_key");
  
  // KPI Query (Refactored for better performance)
  $kpis = fetch_all($mysqli, "
    SELECT
      SUM(net_amount) AS sales_30d,
      SUM(quantity)   AS qty_30d,
      COUNT(DISTINCT customer_id) AS buyers_30d
    FROM fact_sales
    WHERE date_key >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
  ");
  
  if ($kpis) {
    $data['kpi'] = $kpis[0];
  }

  $mysqli->close();

} catch (mysqli_sql_exception $e) {
  // จัดการ Error (เช่น log $e->getMessage())
  http_response_code(500);
  $data['error'] = 'Database query failed: ' . $e->getMessage();
  // ใน production, ไม่ควรแสดง $e->getMessage() ให้ user เห็น
} catch (Exception $e) {
  http_response_code(500);
  $data['error'] = 'Database connection failed: ' . $e->getMessage();
}

// Helper for number format
function nf($n) {
  return number_format((float)$n, 2);
}
?>
<!doctype html>
<html lang="th" data-bs-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Retail DW Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <style>
    /* ใช้ตัวแปร CSS ของ Bootstrap dark mode เป็นหลัก */
    body {
      background: #0f172a; /* Custom dark bg */
      color: var(--bs-body-color);
    }
    .card {
      background: #111827; /* Custom card bg */
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 1rem;
    }
    .card-title {
      color: #e5e7eb;
    }
    .kpi-value {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--bs-body-color);
    }
    .kpi-icon {
      font-size: 2.5rem;
      color: #3b82f6; /* A splash of color */
      opacity: 0.6;
    }
    .sub-title {
      color: #93c5fd;
      font-size: .9rem;
    }
    canvas {
      max-height: 360px;
    }
  </style>
</head>
<body class="p-3 p-md-4">
  <div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <h2 class="mb-0">ยอดขาย (Retail DW) — Dashboard</h2>
      <span class="sub-title">แหล่งข้อมูล: MySQL (mysqli)</span>
    </div>

    <?php if ($data['error']): ?>
      <div class="alert alert-danger">
        <strong>Error:</strong> <?= htmlspecialchars($data['error']) ?>
      </div>
    <?php else: ?>

      <div class="row g-3 g-lg-4 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title text-muted fw-normal">ยอดขาย 30 วัน</h5>
                <div class="kpi-value">฿<?= nf($data['kpi']['sales_30d']) ?></div>
              </div>
              <i class="bi bi-cash-coin kpi-icon"></i>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title text-muted fw-normal">จำนวนชิ้นขาย 30 วัน</h5>
                <div class="kpi-value"><?= number_format((int)$data['kpi']['qty_30d']) ?> ชิ้น</div>
              </div>
              <i class="bi bi-box-seam kpi-icon"></i>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card p-3">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title text-muted fw-normal">จำนวนผู้ซื้อ 30 วัน</h5>
                <div class="kpi-value"><?= number_format((int)$data['kpi']['buyers_30d']) ?> คน</div>
              </div>
              <i class="bi bi-people-fill kpi-icon"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 g-lg-4">

        <div class="col-12 col-lg-8">
          <div class="card p-3">
            <h5 class="card-title mb-2">ยอดขายรายเดือน (2 ปี)</h5>
            <canvas id="chartMonthly"></canvas>
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <div class="card p-3">
            <h5 class="card-title mb-2">สัดส่วนยอดขายตามหมวด</h5>
            <canvas id="chartCategory"></canvas>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="card p-3">
            <h5 class="card-title mb-2">Top 10 สินค้าขายดี</h5>
            <canvas id="chartTopProducts"></canvas>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="card p-3">
            <h5 class="card-title mb-2">ยอดขายตามภูมิภาค</h5>
            <canvas id="chartRegion"></canvas>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="card p-3">
            <h5 class="card-title mb-2">วิธีการชำระเงิน</h5>
            <canvas id="chartPayment"></canvas>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="card p-3">
            <h5 class="card-title mb-2">ยอดขายรายชั่วโมง</h5>
            <canvas id="chartHourly"></canvas>
          </div>
        </div>

        <div class="col-12">
          <div class="card p-3">
            <h5 class="card-title mb-2">ลูกค้าใหม่ vs ลูกค้าเดิม (รายวัน)</h5>
            <canvas id="chartNewReturning"></canvas>
          </div>
        </div>

      </div>
    <?php endif; ?>
  </div>

<script>
// เตรียมข้อมูลจาก PHP -> JS
const data = <?= json_encode($data, JSON_UNESCAPED_UNICODE) ?>;

// Utility: pick labels & values
const toXY = (arr, x, y) => ({ labels: arr.map(o => o[x]), values: arr.map(o => parseFloat(o[y])) });

// === Base Chart Options (for DRY code) ===
const baseChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: { color: '#e5e7eb' }
    }
  },
  scales: {
    x: {
      ticks: { color: '#c7d2fe' },
      grid: { color: 'rgba(255,255,255,.08)' }
    },
    y: {
      ticks: { color: '#c7d2fe' },
      grid: { color: 'rgba(255,255,255,.08)' }
    }
  }
};

const basePieOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { color: '#e5e7eb' }
    }
  }
};
// =========================================


// Monthly
(() => {
  const {labels, values} = toXY(data.monthly, 'ym', 'net_sales');
  new Chart(document.getElementById('chartMonthly'), {
    type: 'line',
    data: { labels, datasets: [{ label: 'ยอดขาย (฿)', data: values, tension: .25, fill: true, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.2)' }] },
    options: baseChartOptions
  });
})();

// Category
(() => {
  const {labels, values} = toXY(data.category, 'category', 'net_sales');
  new Chart(document.getElementById('chartCategory'), {
    type: 'doughnut',
    data: { labels, datasets: [{ data: values }] },
    options: basePieOptions
  });
})();

// Top products
(() => {
  const labels = data.topProducts.map(o => o.product_name);
  const qty = data.topProducts.map(o => parseInt(o.qty_sold));
  new Chart(document.getElementById('chartTopProducts'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'ชิ้นที่ขาย', data: qty, backgroundColor: '#10b981' }] },
    options: {
      ...baseChartOptions, // Use base options
      indexAxis: 'y', // Override specific options
    }
  });
})();

// Region
(() => {
  const {labels, values} = toXY(data.region, 'region', 'net_sales');
  new Chart(document.getElementById('chartRegion'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'ยอดขาย (฿)', data: values, backgroundColor: '#8b5cf6' }] },
    options: baseChartOptions
  });
})();

// Payment
(() => {
  const {labels, values} = toXY(data.payment, 'payment_method', 'net_sales');
  new Chart(document.getElementById('chartPayment'), {
    type: 'pie',
    data: { labels, datasets: [{ data: values }] },
    options: basePieOptions
  });
})();

// Hourly
(() => {
  const {labels, values} = toXY(data.hourly, 'hour_of_day', 'net_sales');
  new Chart(document.getElementById('chartHourly'), {
    type: 'bar',
    data: { labels, datasets: [{ label: 'ยอดขาย (฿)', data: values, backgroundColor: '#ec4899' }] },
    options: baseChartOptions
  });
})();

// New vs Returning
(() => {
  const labels = data.newReturning.map(o => o.date_key);
  const newC = data.newReturning.map(o => parseFloat(o.new_customer_sales));
  const retC = data.newReturning.map(o => parseFloat(o.returning_sales));
  new Chart(document.getElementById('chartNewReturning'), {
    type: 'line',
    data: { labels,
      datasets: [
        { label: 'ลูกค้าใหม่ (฿)', data: newC, tension: .25, fill: false, borderColor: '#f59e0b' },
        { label: 'ลูกค้าเดิม (฿)', data: retC, tension: .25, fill: false, borderColor: '#0ea5e9' }
      ]
    },
    options: {
        ...baseChartOptions,
        scales: {
            ...baseChartOptions.scales,
            x: { ...baseChartOptions.scales.x, ticks: { ...baseChartOptions.scales.x.ticks, maxTicksLimit: 12 } }
        }
    }
  });
})();
</script>

</body>
</html>