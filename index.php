<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Gondwana Rates</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">
  <div class="container py-4">
    <h3 class="mb-3">Rates Request</h3>

    <div class="card mb-3">
      <div class="card-body">
        <form id="ratesForm">
          <div class="mb-2">
            <label class="form-label">Unit Name</label>
            <select class="form-control" id="unitName" required>
              <option value="Unit A" selected>Unit A</option>
              <option value="Unit B">Unit B</option>
            </select>
          </div>

          <div class="row">
            <div class="col-md-6 mb-2">
              <label class="form-label">Arrival (dd/mm/yyyy)</label>
              <input class="form-control" id="arrival" value="01/10/2025" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label">Departure (dd/mm/yyyy)</label>
              <input class="form-control" id="departure" value="05/10/2025" required>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label">Occupants</label>
            <input type="number" min="1" class="form-control" id="occupants" value="2" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Ages (comma separated)</label>
            <input class="form-control" id="ages" value="30,10">
          </div>

          <button type="submit" class="btn btn-primary">Get Rates</button>
        </form>
      </div>
    </div>

    <div id="resultArea" style="display:none;">
      <h5>Results</h5>
      <table class="table table-bordered bg-white" id="resultTable">
        <thead><tr><th>Unit Name</th><th>Rate</th><th>Date Range</th><th>Availability</th></tr></thead>
        <tbody></tbody>
      </table>

      <!--
        Raw response display area (for debugging purposes)
        commented by MNhaiyala
      -->
      <!--<pre id="rawResp" class="p-2 bg-dark text-white" style="max-height:300px;overflow:auto"></pre> -->
    </div>

  </div>

  <!-- Modal to show rate details -->
  <div class="modal fade" id="ratesModal" tabindex="-1" aria-labelledby="ratesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ratesModalLabel">Rate Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="modalContent">
          <!-- Dynamic modal content -->
          <!-- commented by MNhaiyala: populated with detailed info for each leg of the rate -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

<script>
$(document).ready(function() {
  $('#ratesForm').on('submit', function(e) {
    e.preventDefault();

    const unitName = $('#unitName').val().trim();
    const arrival = $('#arrival').val().trim();
    const departure = $('#departure').val().trim();
    const occupants = parseInt($('#occupants').val(), 10) || 1;
    const agesRaw = $('#ages').val().trim();
    const ages = agesRaw ? agesRaw.split(',').map(s => parseInt(s.trim(),10)).filter(x => !isNaN(x)) : [];

    const payload = {
      "Unit Name": unitName,
      "Arrival": arrival,
      "Departure": departure,
      "Occupants": occupants,
      "Ages": ages
    };

    console.log(payload);

    $.ajax({
      url: 'api/rates.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify(payload),
      dataType: 'json',
      success: function(json) {
        $('#resultArea').show();
        const tbody = $('#resultTable tbody');
        tbody.empty();

        const remote = json.remote_response || {};
        let displayRate = "N/A";
        let availability = "Unknown";

        if (remote["Total Charge"]) displayRate = remote["Total Charge"];
        if (remote.Available !== undefined) availability = remote.Available ? "Available" : "Not available";

        tbody.append(`<tr>
          <td>${unitName}</td>
          <td>${displayRate}</td>
          <td>${arrival} → ${departure}</td>
          <td>${availability} <button class="btn btn-sm btn-info ms-2 view-details">View Details</button></td>
        </tr>`);

        // Build modal content
        const modalHtml = [];
        if (remote.Legs && Array.isArray(remote.Legs)) {
          remote.Legs.forEach((leg, i) => {
            modalHtml.push(`<div class="card mb-2">
              <div class="card-header">Leg ${i+1}: ${leg["Special Rate Description"]}</div>
              <div class="card-body">
                <p><strong>Effective Average Daily Rate:</strong> ${leg["Effective Average Daily Rate"]}</p>
                <p><strong>Total Charge:</strong> ${leg["Total Charge"]}</p>
                <p><strong>Category:</strong> ${leg["Category"]}</p>
                <p><strong>Guests:</strong></p>
                <ul>
                  ${leg.Guests.map(g => `<li>${g["Age Group"]} - Age: ${g.Age || "-"} - ${g["Error Message"] || ""}</li>`).join('')}
                </ul>
              </div>
            </div>`);
          });
        }
        $('#modalContent').html(modalHtml.join(''));

        $('.view-details').on('click', function() {
          const modal = new bootstrap.Modal(document.getElementById('ratesModal'));
          modal.show();
        });
      },
      error: function(xhr) { 
        console.log('Error', xhr.status, xhr.responseText); 
      }
    });
  });
});
</script>
</body>
</html>
