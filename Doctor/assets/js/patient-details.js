$(document).ready(function() {
    console.log('patient-details.js loaded successfully');

    // Initialize DataTable and fetch visit history
    function loadVisitHistory() {
        const patientId = <?php echo json_encode($patientId); ?>;
        $.ajax({
            url: 'fetch_visit_history.php',
            method: 'GET',
            data: { patient_id: patientId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const tbody = $('#visitHistoryTable tbody');
                    tbody.empty();
                    response.visits.forEach(visit => {
                        const date = moment(visit.visit_date, 'YYYY-MM-DD HH:mm:ss').format('DD/MM/YYYY HH:mm:ss');
                        tbody.append(`
                            <tr>
                                <td>${date}</td>
                                <td>${visit.reason_for_visiting}</td>
                                <td>${visit.notes_outcome || 'N/A'}</td>
                            </tr>
                        `);
                    });

                    // Initialize DataTable with responsive feature
                    $('#visitHistoryTable').DataTable({
                        paging: true,
                        searching: true,
                        ordering: true,
                        info: true,
                        lengthMenu: [5, 10, 25, 50],
                        pageLength: 5,
                        responsive: true, // Enable responsive extension
                        columnDefs: [
                            { width: "20%", targets: 0 }, // Date
                            { width: "40%", targets: 1 }, // Reason
                            { width: "40%", targets: 2 }  // Results and Treatment
                        ]
                    });
                } else {
                    console.error('Error fetching visit history:', response.message);
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error fetching visit history:', status, error);
                alert('Error fetching visit history: ' + error);
            }
        });
    }
    loadVisitHistory();

    // Handle Visit Form Submission
    $('#visitForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Visit form submitted');

        // Validate Reason for Visit
        const reason = $('textarea[name="reason"]').val().trim();
        if (!reason) {
            console.warn('Validation failed: Reason for Visit is empty');
            alert('Reason for Visit is required.');
            return;
        }

        // Combine treatment, prescriptions, procedure, and note into notes_outcome
        const treatment = $('textarea[name="treatment"]').val().trim();
        const prescriptions = $('textarea[name="prescriptions"]').val().trim();
        const procedure = $('textarea[name="procedure"]').val().trim();
        const note = $('textarea[name="note"]').val().trim();

        let noteOutcomeParts = [];
        if (treatment) noteOutcomeParts.push(`Treatment: ${treatment}`);
        if (prescriptions) noteOutcomeParts.push(`Prescription: ${prescriptions}`);
        if (procedure) noteOutcomeParts.push(`Procedure: ${procedure}`);
        if (note) noteOutcomeParts.push(`Additional Note: ${note}`);
        const noteOutcome = noteOutcomeParts.join('\n');

        // Prepare form data
        const formData = $(this).serializeArray();
        formData.push({ name: 'notes_outcome', value: noteOutcome });

        console.log('Submitting form data:', formData);

        $.ajax({
            url: 'save_visit.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    $('#successMessage').text(response.message);
                    $('#successModal').modal('show');
                    $('#visitForm')[0].reset();
                    // Reset the date to today's date
                    $('input[name="visit_date"]').val('<?php echo $currentDate; ?>');
                    loadVisitHistory();
                } else {
                    console.error('Submission failed:', response.message);
                    $('#successMessage').text('Failed to record visit: ' + response.message);
                    $('#successModal').modal('show');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error, xhr.responseText);
                $('#successMessage').text('Error saving visit: ' + error + ' (Check console for details)');
                $('#successModal').modal('show');
            }
        });
    });

    // Handle Payment Request Form Submission
    $('#paymentRequestForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Payment request form submitted');

        const formData = $(this).serialize() + '&patient_id=<?php echo json_encode($patientId); ?>';
        console.log('Submitting payment request data:', formData);

        $.ajax({
            url: 'request_payment.php',
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    $('#successMessage').text(response.message);
                    $('#successModal').modal('show');
                    $('#paymentRequestForm')[0].reset();
                } else {
                    console.error('Payment request failed:', response.message);
                    $('#successMessage').text('Failed to request payment: ' + response.message);
                    $('#successModal').modal('show');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', status, error, xhr.responseText);
                $('#successMessage').text('Error requesting payment: ' + error);
                $('#successModal').modal('show');
            }
        });
    });

    // Download Medical History as PDF
    $('#downloadMedicalHistory').on('click', function(e) {
        e.preventDefault();
        const patientId = <?php echo json_encode($patientId); ?>;
        $.ajax({
            url: 'fetch_visit_history.php',
            method: 'GET',
            data: { patient_id: patientId },
            dataType: 'json',
            success: function(response) {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();
                let y = 20;

                doc.setFontSize(18);
                doc.text('Medical History Report', 105, y, null, null, 'center');
                y += 10;
                doc.setFontSize(12);
                doc.text(`Patient: <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>`, 20, y);
                y += 10;
                doc.text(`Patient ID: <?php echo $patientId; ?>`, 20, y);
                y += 10;

                const patientInfo = [
                    `Contact: <?php echo htmlspecialchars($patient['contact_number'] ?? 'N/A'); ?>`,
                    `Email: <?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?>`,
                    `Date of Birth: <?php echo htmlspecialchars($patient['date_of_birth']); ?>`,
                    `Address: <?php echo htmlspecialchars($patient['address'] ?? 'N/A'); ?>`,
                    `Gender: <?php echo htmlspecialchars($patient['gender'] ?? 'N/A'); ?>`
                ];
                doc.text(patientInfo, 20, y, { maxWidth: 180, align: 'left' });
                y += 40;

                doc.text('Visit History', 20, y);
                y += 10;
                if (response.success && response.visits.length > 0) {
                    response.visits.forEach((visit, index) => {
                        y += 10;
                        doc.text(`Visit ${index + 1} - Date: ${moment(visit.visit_date).format('DD/MM/YYYY HH:mm:ss')}`, 20, y);
                        y += 5;
                        doc.text(`Reason: ${visit.reason_for_visiting}`, 20, y);
                        y += 5;
                        doc.text(`Outcome: ${visit.notes_outcome || 'N/A'}`, 20, y);
                        y += 10;
                    });
                } else {
                    doc.text('No visit history available.', 20, y);
                }

                doc.save('medical_history_<?php echo $patientId; ?>.pdf');
            },
            error: function(xhr, status, error) {
                alert('Error fetching visit history for PDF: ' + error);
            }
        });
    });
});