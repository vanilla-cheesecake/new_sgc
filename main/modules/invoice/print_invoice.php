<?php
include('connect.php');
session_start();
$salesInvoice = null;
$salesInvoiceItems = [];

// Check if the 'poID' parameter is set
if (isset($_GET['invoiceID'])) {
    $invoiceID = $_GET['invoiceID'];

    // Query to retrieve purchase order details
    $query = "SELECT * FROM sales_invoice WHERE invoiceID = :invoiceID";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':invoiceID', $invoiceID);
    $stmt->execute();

    // Fetch purchase order details
    $salesInvoice = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if purchase order details are found
    if ($salesInvoice) {
        // Fetch purchase order items
        $queryInvoiceItems = "SELECT * FROM sales_invoice_items WHERE salesInvoiceID = :invoiceID";
        $stmtInvoiceItems = $db->prepare($queryInvoiceItems);
        $stmtInvoiceItems->bindParam(':invoiceID', $invoiceID);
        $stmtInvoiceItems->execute();

        $salesInvoiceItems = $stmtInvoiceItems->fetchAll(PDO::FETCH_ASSOC);

        $grossAmount = $salesInvoice['grossAmount'];
        $vatPercentage = $salesInvoice['vatPercentage'];
        $vatAmount = number_format($grossAmount / (1 + $vatPercentage / 100) * ($vatPercentage / 100), 2);
        $wvatPercentage = $salesInvoice['taxWithheldPercentage'];
        $netOfVat = $salesInvoice['netOfVat']; // Total amount without VAT
        $netAmountDue = $salesInvoice['netAmountDue']; // Total amount with VAT
        $wvatAmount = number_format(($netOfVat * $wvatPercentage) / 100, 2);

    } else {
        // Redirect or display an error if purchase order details are not found
        header("Location: index.php"); // Redirect to the main page or display an error message
        exit();
    }
} else {
    // Redirect or display an error if 'poID' parameter is not set
    header("Location: index.php"); // Redirect to the main page or display an error message
    exit();
}




// Fetch product items
$query = "SELECT itemName, itemSalesInfo, itemSrp FROM items";
$result = $db->query($query);

$productItems = array();

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $productItems[] = $row;
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" type="image/x-icon" href="../images/conogas.png">
<link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900' type='text/css'>
<title>Invoice Print</title>
<meta name="author" content="harnishdesign.net">
</head>
<style>
    body {
        font-family: calibri;
    }
    .invoiceno {
        position: absolute;
        left: 1.4em;
        top: 7em;
        font-size: 15px;
    }
    .address {
        position: absolute;
        left: 1.4em;
        top: 8em;
        font-size: 15px;  
    }
    .terms {
        position: absolute;
        left: 34em;
        top: 9.9em;
        font-size: 15px;
    }
    .bstyle {
        position: absolute;
        left: 310px;
        top: 180px;
        font-size: 15px;
    }
    .tin {
        position: absolute;
        left: 50px;
        top: 180px;
        font-size: 15px;
    }
    table {
        position: absolute;
        top: 239px;
        left: 18px;
        width: 96%;
        font-size: 15px;
    }
    .totalamount {
        position: absolute;
        right: 14px;
        bottom: 310px;
        font-size: 15px;
    }
    .netofvat {
        position: absolute;
        right: 14px;
        bottom: 272px;
        font-size:15px;
    }
    .netamount {
        position: absolute;
        right: 14px;
        bottom: 153px;
        font-size: 15px;   
    }
    .vat {
        position: absolute;
        left: 250px;
        bottom: 170px;
        font-size: 15px; 
    }
    .wvat{
        position: absolute;
        right: 14px;
        bottom: 215px;
        font-size: 15px; 
    }
    .vat1{
        position: absolute;
        right: 14px;
        bottom: 290px;
        font-size: 15px; 
    }
    .nad {
        position: absolute;
        right: 14px;
        bottom: 200px;
        font-size: 15px;   
    }
    .ves {
        position: absolute;
        left: 250px;
        bottom: 200px;
        font-size: 15px; 
    }
    
</style>
<body>
    <div class="invoiceno"><?php echo $salesInvoice['customer']; ?></div>
    <div class="address"><?php echo $salesInvoice['address']; ?></div>  
    <div class="terms"><?php echo $salesInvoice['terms']; ?></div>
    <div class="tin"><?php echo $salesInvoice['invoiceTin']; ?></div> 
    <div class="bstyle"><?php echo $salesInvoice['invoiceBusinessStyle']; ?></div>  
    <table class="table">
    <tbody>
        <?php
        // Connect to your database
        $servername = "localhost";
        $username = "root";
        $password = "digimax2023";
        $dbname = "sgc_db";

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $invoiceID = $_GET['invoiceID'];

        // Query to fetch rows from the database
        $sql = "SELECT `itemID`, `item`, `description`, `quantity`, `uom`, `rate`, `amount`, `status`, `created_at` FROM `sales_invoice_items`
            WHERE `salesInvoiceId` = '$invoiceID'";

        $result = $conn->query($sql);

        // If rows are found, display them in HTML table rows
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td style='text-align: left; padding-bottom: 2.4px; white-space: nowrap;'>" . $row["description"] . "</td>
                    <td style='padding-left: 170px; padding-bottom: 2.4px;'>" . $row["quantity"] . "</td>
                    <td style='padding-bottom: 2.4px; white-space: nowrap;'>" . $row["uom"] . "</td>
                    <td style='text-align: right; padding-right: 50px; padding-bottom: 2.4px;'>" . $row["rate"] . "</td>
                    <td style='text-align: right; padding-bottom: 2.4px;'>" . $row["amount"] . "</td>
                </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No items found</td></tr>";
        }

        // Close the database connection
        $conn->close();
        ?>
    </tbody>
</table>
<div class="totalamount"><?php echo $salesInvoice['netAmountDue']; ?></div>
<div class="netofvat"><?php echo $salesInvoice['netOfVat']; ?></div>
<div class="netamount"><?php echo $salesInvoice['totalAmountDue']; ?></div>
<div class="vat"><?php echo $vatAmount; ?></div>
<div class="ves"><?php echo $salesInvoice['netOfVat']; ?></div>  
<div class="wvat"><?php echo $wvatAmount; ?></div>
<div class="vat1"><?php echo $vatAmount; ?></div>
<div class="nad"><?php echo $salesInvoice['netAmountDue']; ?></div>           
</body>
<script>
    window.onload = function() {
            window.print(); // Automatically print the page when it loads
        };

        // Event listener for the afterprint event
        window.addEventListener('afterprint', function(event) {
            // If the afterprint event is triggered due to user cancelling print
            if (!event.returnValue) {
                // Redirect to the previous page
                window.history.back();
            }
        });
</script>
</html>