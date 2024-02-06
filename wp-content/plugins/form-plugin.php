<?php
/*
Plugin Name: Custom Form Plugin
Description: Custom form with database storage and certificate generation.
Version: 1.0
Author: Dimitar
*/

require_once(plugin_dir_path(__FILE__) . 'tcpdf/tcpdf.php');
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

function validate_form_fields() {
    $errors = array();

    if (empty($_POST['email'])) {
        $errors['email'] = 'Email';
    }

    if (empty($_POST['first_name'])) {
        $errors['first_name'] = 'First Name';
    }

    if (empty($_POST['last_name'])) {
        $errors['last_name'] = 'Last Name';
    }

    if (empty($_POST['date_of_birth'])) {
        $errors['date_of_birth'] = 'Date of Birth';
    }

    if (empty($_POST['phone_number'])) {
        $errors['phone_number'] = 'Phone Number';
    }

    return $errors;
}

function create_custom_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'custom_form_data';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        email VARCHAR(100) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        date_of_birth DATE NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_custom_table');

function handle_form_submission() {
    if (isset($_POST['submit_form'])) {
        $errors = validate_form_fields();

        foreach ($errors as $field => $error) {
            echo '<p style="color: red;">The ' . ucfirst($field) . ' is mandatory to submit the form</p>';
        }

        if (empty($errors)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'custom_form_data';

            $wpdb->insert(
                $table_name,
                array(
                    'email' => sanitize_email($_POST['email']),
                    'first_name' => sanitize_text_field($_POST['first_name']),
                    'last_name' => sanitize_text_field($_POST['last_name']),
                    'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
                    'phone_number' => sanitize_text_field($_POST['phone_number']),
                )
            );

            send_certificate_email($_POST['email'], $_POST);
        }
    }
}

add_action('init', 'handle_form_submission');

function send_certificate_email($email, $form_data) {
    $pdf_content = generate_certificate_pdf($form_data);

    $upload_dir = wp_upload_dir();
    $certificate_path = $upload_dir['path'] . '/certificate.pdf';

    file_put_contents($certificate_path, $pdf_content);

    $download_link = $upload_dir['url'] . '/certificate.pdf';

    $headers = array(
        'Content-Type: application/pdf; charset=UTF-8',
        'Content-Disposition: attachment; filename="certificate.pdf"',
    );

    $attachments = array(
        'certificate.pdf' => $certificate_path,
    );

    $message = "Congratulations for filling up our form! <br> Please find below a link where you can download your certificate:\n<a href='" . esc_url($download_link) . "'>Download PDF</a>";

    return wp_mail($email, 'Congratulations on your certificate!', $message, $headers, $attachments);
}


function generate_certificate_pdf($data) {
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('Certificate');
    $pdf->SetSubject('Certificate');
    $pdf->SetKeywords('Certificate, TCPDF, PDF, WordPress');

    $pdf->SetHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
    $pdf->SetFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 16);

    $pdf->Cell(0, 10, 'Certificate of Achievement', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'This is to certify that ' . $data['first_name'] . ' ' . $data['last_name'] . ' has successfully completed our form.', 0, 1, 'C');
    $pdf->Cell(0, 10, 'Date of Birth: ' . $data['date_of_birth'], 0, 1, 'C');
    $pdf->Cell(0, 10, 'Email: ' . $data['email'], 0, 1, 'C');
    $pdf->Cell(0, 10, 'Phone Number: ' . $data['phone_number'], 0, 1, 'C');

    return $pdf->Output('', 'S');
}

function form_plugin()
{
    if (isset($_POST['submit_form'])) {
        $errors = validate_form_fields();
    }
    ?>
    <form method="post" action="" style="text-align:center; margin-top: 50px;">
        <div>
            <label>Email: </label>
            <input type="email" name="email" value="<?php echo esc_attr($_POST['email']); ?>">
            <?php echo isset($errors['email']) ? '<p style="color: red; font-size:10px;">The Email is mandatory to submit the form</p>' : ''; ?>
        </div>
        <div>
            <label>First Name: </label>
            <input type="text" name="first_name" value="<?php echo esc_attr($_POST['first_name']); ?>">
            <?php echo isset($errors['first_name']) ? '<p style="color: red;font-size:10px;">The First Name is mandatory to submit the form</p>' : ''; ?>
        </div>
        <div>
            <label>Last Name: </label>
            <input type="text" name="last_name" value="<?php echo esc_attr($_POST['last_name']); ?>">
            <?php echo isset($errors['last_name']) ? '<p style="color: red;font-size:10px;">The Last Name is mandatory to submit the form</p>' : ''; ?>
        </div>
        <div>
            <label>Date of Birth: </label>
            <input type="date" name="date_of_birth" value="<?php echo esc_attr($_POST['date_of_birth']); ?>">
            <?php echo isset($errors['date_of_birth']) ? '<p style="color: red;font-size:10px;">The Date of Birth is mandatory to submit the form</p>' : ''; ?>
        </div>
        <div>
            <label>Phone Number: </label>
            <input type="tel" name="phone_number" value="<?php echo esc_attr($_POST['phone_number']); ?>">
            <?php echo isset($errors['phone_number']) ? '<p style="color: red;font-size:10px;">The Phone Number is mandatory to submit the form</p>' : ''; ?>
        </div>
        <input type="submit" name="submit_form" value="Submit">
    </form>
    <?php
}

add_shortcode('form', 'form_plugin');
?>
