<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      exit(0);
}

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/.env')) {
      $envFile = file_get_contents(__DIR__ . '/.env');
      $lines = explode("\n", $envFile);
      foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                  continue; // Skip empty lines and comments
            }
            if (strpos($line, '=') !== false) {
                  list($key, $value) = explode('=', $line, 2);
                  $key = trim($key);
                  $value = trim($value);
                  if (!getenv($key)) {
                        putenv("$key=$value");
                        $_ENV[$key] = $value;
                        $_SERVER[$key] = $value;
                  }
            }
      }
}

// Get Airtable credentials from environment variables
$AIRTABLE_API_KEY = getenv('AIRTABLE_API_KEY');
$AIRTABLE_BASE_ID = getenv('AIRTABLE_INVENTORY_BASE_ID');
$AIRTABLE_TABLE_NAME = 'Inventory';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$request = isset($_GET['action']) ? $_GET['action'] : '';
$data = json_decode(file_get_contents('php://input'), true);

// Route requests
switch ($request) {
  case 'status':
          checkStatus();
          break;
  case 'read':
          readInventory();
          break;
  case 'create':
          createItem($data);
          break;
  case 'update':
          updateItem($data);
          break;
  case 'delete':
          deleteItem($data);
          break;
  default:
          http_response_code(400);
          echo json_encode(['error' => 'Invalid action']);
}

// Check configuration status (for diagnostics)
function checkStatus() {
      global $AIRTABLE_API_KEY, $AIRTABLE_BASE_ID;

      $status = [
            'env_file_exists' => file_exists(__DIR__ . '/.env'),
            'api_key_set' => !empty($AIRTABLE_API_KEY),
            'base_id_set' => !empty($AIRTABLE_BASE_ID),
            'api_key_length' => $AIRTABLE_API_KEY ? strlen($AIRTABLE_API_KEY) : 0,
            'base_id_length' => $AIRTABLE_BASE_ID ? strlen($AIRTABLE_BASE_ID) : 0,
            'php_version' => phpversion(),
            'curl_available' => function_exists('curl_init')
      ];

      $allConfigured = $status['api_key_set'] && $status['base_id_set'];

      echo json_encode([
            'success' => $allConfigured,
            'configured' => $allConfigured,
            'status' => $status,
            'message' => $allConfigured
                  ? 'API is properly configured'
                  : 'Missing Airtable credentials. Please set AIRTABLE_API_KEY and AIRTABLE_INVENTORY_BASE_ID'
      ]);
}

// Read all inventory items
function readInventory() {
      global $AIRTABLE_API_KEY, $AIRTABLE_BASE_ID, $AIRTABLE_TABLE_NAME;

      // Validate credentials
      if (empty($AIRTABLE_API_KEY) || empty($AIRTABLE_BASE_ID)) {
            http_response_code(500);
            echo json_encode([
                  'success' => false,
                  'error' => 'Server configuration error: Airtable credentials not set. Please configure AIRTABLE_API_KEY and AIRTABLE_INVENTORY_BASE_ID environment variables.'
            ]);
            return;
      }

    $AIRTABLE_URL = "https://api.airtable.com/v0/{$AIRTABLE_BASE_ID}/{$AIRTABLE_TABLE_NAME}";

    $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $AIRTABLE_URL);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
                          'Authorization: Bearer ' . $AIRTABLE_API_KEY
                      ]);

    $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlError = curl_error($ch);
      curl_close($ch);

      // Check for curl errors
      if ($curlError) {
            http_response_code(500);
            echo json_encode([
                  'success' => false,
                  'error' => 'Network error: ' . $curlError
            ]);
            return;
      }

    if ($httpCode == 200) {
              $records = json_decode($response, true);

          if (isset($records['records'])) {
                        $items = [];
                        foreach ($records['records'] as $record) {
                                          $fields = $record['fields'];
                                          $quantity = isset($fields['quantity']) ? intval($fields['quantity']) : 0;
                                          $cost = isset($fields['cost']) ? floatval($fields['cost']) : 0;

                            $items[] = [
                                                  'id' => $record['id'],
                                                  'name' => $fields['name'] ?? '',
                                                  'sku' => $fields['sku'] ?? '',
                                                  'quantity' => $quantity,
                                                  'cost' => $cost,
                                                  'location' => $fields['location'] ?? '',
                                                  'totalValue' => $quantity * $cost
                                              ];
                        }
                        echo json_encode(['success' => true, 'data' => $items]);
          } else {
                        echo json_encode(['success' => true, 'data' => []]);
          }
    } else {
              http_response_code($httpCode);
              $errorDetail = json_decode($response, true);
              $errorMessage = isset($errorDetail['error']['message'])
                    ? $errorDetail['error']['message']
                    : $response;

              echo json_encode([
                    'success' => false,
                    'error' => 'Airtable API error (HTTP ' . $httpCode . '): ' . $errorMessage
              ]);
    }
}

// Create new item
function createItem($data) {
      global $AIRTABLE_API_KEY, $AIRTABLE_BASE_ID, $AIRTABLE_TABLE_NAME;

    $AIRTABLE_URL = "https://api.airtable.com/v0/{$AIRTABLE_BASE_ID}/{$AIRTABLE_TABLE_NAME}";

    $fields = [
              'name' => $data['name'] ?? '',
              'sku' => $data['sku'] ?? '',
              'quantity' => intval($data['quantity'] ?? 0),
              'cost' => floatval($data['cost'] ?? 0),
              'location' => $data['location'] ?? ''
          ];

    $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $AIRTABLE_URL);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
                          'Authorization: Bearer ' . $AIRTABLE_API_KEY,
                          'Content-Type: application/json'
                      ]);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['records' => [['fields' => $fields]]]));

    $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

    if ($httpCode == 200) {
              echo json_encode(['success' => true, 'message' => 'Item created']);
    } else {
              http_response_code($httpCode);
              echo json_encode(['error' => 'Failed to create item: ' . $response]);
    }
}

// Update item
function updateItem($data) {
      global $AIRTABLE_API_KEY, $AIRTABLE_BASE_ID, $AIRTABLE_TABLE_NAME;

    $recordId = $data['id'] ?? '';
      $AIRTABLE_URL = "https://api.airtable.com/v0/{$AIRTABLE_BASE_ID}/{$AIRTABLE_TABLE_NAME}/{$recordId}";

    $fields = [
              'name' => $data['name'] ?? '',
              'sku' => $data['sku'] ?? '',
              'quantity' => intval($data['quantity'] ?? 0),
              'cost' => floatval($data['cost'] ?? 0),
              'location' => $data['location'] ?? ''
          ];

    $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $AIRTABLE_URL);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
                          'Authorization: Bearer ' . $AIRTABLE_API_KEY,
                          'Content-Type: application/json'
                      ]);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['fields' => $fields]));

    $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

    if ($httpCode == 200) {
              echo json_encode(['success' => true, 'message' => 'Item updated']);
    } else {
              http_response_code($httpCode);
              echo json_encode(['error' => 'Failed to update item']);
    }
}

// Delete item
function deleteItem($data) {
      global $AIRTABLE_API_KEY, $AIRTABLE_BASE_ID, $AIRTABLE_TABLE_NAME;

    $recordId = $data['id'] ?? '';
      $AIRTABLE_URL = "https://api.airtable.com/v0/{$AIRTABLE_BASE_ID}/{$AIRTABLE_TABLE_NAME}/{$recordId}";

    $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $AIRTABLE_URL);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
                          'Authorization: Bearer ' . $AIRTABLE_API_KEY
                      ]);

    $response = curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

    if ($httpCode == 200) {
              echo json_encode(['success' => true, 'message' => 'Item deleted']);
    } else {
              http_response_code($httpCode);
              echo json_encode(['error' => 'Failed to delete item']);
    }
}
?>
