<?php

// Start session for user authentication
session_start();
// Include the database connection script
include('../../connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Start a transaction
        $db->beginTransaction();

        // Retrieve and sanitize form data
        $invoiceID = $_POST["invoiceID"];

        // Soft delete sales invoice items
        $softDeleteItemsQuery = "UPDATE sales_invoice_items SET status = 'deactivated' WHERE salesInvoiceID = :invoiceID";
        $softDeleteItemsStmt = $db->prepare($softDeleteItemsQuery);
        $softDeleteItemsStmt->bindParam(":invoiceID", $invoiceID);

        if (!$softDeleteItemsStmt->execute()) {
            throw new Exception("Error soft deleting sales invoice items.");
        }

        // Soft delete the sales invoice
        $softDeleteInvoiceQuery = "UPDATE sales_invoice 
                           SET invoiceStatus = 'VOID', 
                               invoiceNo = CONCAT(invoiceNo, '(void)'), 
                               invoicePo = CONCAT(invoicePo, '(void)') 
                           WHERE invoiceID = :invoiceID";
        $softDeleteInvoiceStmt = $db->prepare($softDeleteInvoiceQuery);
        $softDeleteInvoiceStmt->bindParam(":invoiceID", $invoiceID);

        if (!$softDeleteInvoiceStmt->execute()) {
            throw new Exception("Error deleting sales invoice.");
        }

                // Log the soft delete in the audit trail
                $logQuery = "INSERT INTO audit_trail (tableName, recordID, action, userID, details) VALUES (:tableName, :recordID, :action, :userID, :details)";
                $logStmt = $db->prepare($logQuery);
        
                $tableName = "sales_invoice";
                $recordID = $invoiceID;
                $action = "void";
                $userID = $_SESSION['SESS_MEMBER_ID'];
                $details = "Sales Invoice Void with ID: $invoiceID";
        
                $logStmt->bindParam(":tableName", $tableName);
                $logStmt->bindParam(":recordID", $recordID);
                $logStmt->bindParam(":action", $action);
                $logStmt->bindParam(":userID", $userID);
                $logStmt->bindParam(":details", $details);
        
                if (!$logStmt->execute()) {
                    throw new Exception("Error logging audit trail.");
                }
        // After the try-catch block
        if ($db->commit()) {
            // The sales invoice and its items are successfully soft-deleted from the database
            echo json_encode(["status" => "success", "message" => "Sales Invoice Voided!"]);
        } else {
            throw new Exception("Error committing the transaction.");
        }
    } catch (Exception $e) {
        // Rollback the transaction on exception
        $db->rollBack();

        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
