<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repositories\VendorRepository;
use App\Repositories\OrderRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class VendorController {
    private VendorRepository $vendors;
    private OrderRepository $orders;

    public function __construct(VendorRepository $vendors, OrderRepository $orders) {
        $this->vendors = $vendors;
        $this->orders = $orders;
    }

    private function json(Response $res, mixed $data, int $status = 200): Response {
        $res->getBody()->write(json_encode($data,
            JSON_UNESCAPED_UNICODE| JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        ));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function createVendor(Request $req, Response $res): Response {
        $body = (array)$req->getParsedBody();
        $name = trim($body['name'] ?? '');
        $loc = trim($body['location'] ?? '');
        $hours = trim($body['openingHours'] ?? '');
        if (!$name || !$loc || !$hours) return $this->json($res, ['error' => 'name, location, openingHours required'], 400);

        $userId = (int)$req->getAttribute('userId');
        $id = $this->vendors->create($userId, $name, $loc, $hours, $body['imageUrl'] ?? null);
        $ownerName = $this->orders->getUserName($userId);

        return $this->json($res, [
            'id' => $id, 'ownerId' => $userId, 'ownerName' => $ownerName,
            'name' => $name, 'location' => $loc, 'openingHours' => $hours,
            'imageUrl' => $body['imageUrl'] ?? null, 'status' => 'pending'
        ], 201);
    }

    public function getDashboard(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        $vendor = $this->vendors->findVendorByOwnerId($userId);
        if (!$vendor) return $this->json($res, ['error' => 'Vendor record not configured'], 404);

        $currentStatus = $vendor['status'] ?? 'pending';
        if ($currentStatus !== 'active') {
        return $this->json($res, [
            'status'       => $currentStatus,
            'dailyRevenue' => 0.0,
            'totalOrders'  => 0,
            'activeOrders' => []
        ]);
        }

        $vendorId = (int)$vendor['id'];
        $orders = $this->orders->getVendorOrders($vendorId);

        $dailyRevenue = 0.0;
        $todayStr = date('Y-m-d');
        foreach ($orders as $o) {
            if ($o['status'] === 'collected' && str_starts_with($o['created_at'], $todayStr)) {
                $dailyRevenue += (float)$o['total'];
            }
        }

        $activeOrders = array_filter($orders, function($o) {
            return !in_array($o['status'], ['collected', 'cancelled']);
        });

        $formattedOrders = array_map(function($o) {
            return [
                'id'          => (int)$o['id'],
                'userId'      => (int)$o['user_id'],
                'vendorId'    => (int)$o['vendor_id'],
                'vendorName'  => $this->orders->getVendorName((int)$o['vendor_id']),
                'studentName' => $this->orders->getUserName((int)$o['user_id']),
                'status'      => $o['status'],
                'total'       => (float)$o['total'],
                'pickupAt'  => $o['pickup_at'],
                'createdAt'   => $o['created_at'],
                'items'       => array_map(fn($i) => [
                    'id'         => (int)$i['id'],
                    'menuItemId' => (int)$i['menu_item_id'],
                    'name'       => $i['name'],
                    'quantity'   => (int)$i['qty'],
                    'unitPrice'  => (float)$i['unit_price']
                ], $this->orders->getOrderItems((int)$o['id']))
            ];
        }, array_values($activeOrders));

        return $this->json($res, [
            'id'           => $vendorId,
            'isOpen'       => (bool)$vendor['is_open'],
            'dailyRevenue' => $dailyRevenue,
            'totalOrders'  => count($orders),
            'activeOrders' => $formattedOrders,
            'liveQueue'    => $formattedOrders,
            'status'       => $currentStatus,
            'isActive'     => ($currentStatus === 'active')
        ]);
    }

    public function updateOrderStatus(Request $req, Response $res, array $args): Response {
        $body = (array)$req->getParsedBody();
        $status = $body['status'] ?? '';
        if (!$status) return $this->json($res, ['error' => 'Status field required'], 400);

        $id = (int)$args['id'];
        $userId = (int)$req->getAttribute('userId');
        $role = $req->getAttribute('role');

        if ($role !== 'admin') {
            $vendor = $this->vendors->findVendorByOwnerId($userId);
            $order = $this->orders->findById($id);
            if (!$vendor || !$order || (int)$order['vendor_id'] !== (int)$vendor['id']) {
                return $this->json($res, ['error' => 'Access Denied: You do not own this stall'], 403);
            }
        }

        $this->orders->updateStatus($id, $status);
        $updatedOrder = $this->orders->findById($id);

        return $this->json($res, [
            'id'          => (int)$updatedOrder['id'],
            'userId'      => (int)$updatedOrder['user_id'],
            'vendorId'    => (int)$updatedOrder['vendor_id'],
            'vendorName'  => $this->orders->getVendorName((int)$updatedOrder['vendor_id']),
            'studentName' => $this->orders->getUserName((int)$updatedOrder['user_id']),
            'status'      => $updatedOrder['status'],
            'total'       => (float)$updatedOrder['total'],
            'pickupTime'  => $updatedOrder['pickup_at'],
            'createdAt'   => $updatedOrder['created_at'],
            'items'       => array_map(fn($i) => [
                'id'         => (int)$i['id'],
                'menuItemId' => (int)$i['menu_item_id'],
                'name'       => $i['name'],
                'quantity'   => (int)$i['qty'],
                'unitPrice'  => (float)$i['unit_price']
            ], $this->orders->getOrderItems($id))
        ]);
    }

    public function getProfile(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        
        // 2. Fetch the vendor record linked to this user owner ID
        $vendor = $this->vendors->findVendorByOwnerId($userId);
        if (!$vendor) {
            return $this->json($res, ['error' => 'Vendor record not configured'], 404);
        }
        
        // 3. Return the exact database fields formatted to match what VendorProfile.vue expects
        return $this->json($res, [
            'id'           => (int)$vendor['id'],
            'name'         => $vendor['name'],
            'location'     => $vendor['location'],
            'openingHours' => $vendor['opening_hours'] ?? '8:00 AM - 5:00 PM',
            'status'       => $vendor['status'],
            'ownerName'    => $this->orders->getUserName((int)$vendor['owner_id']) 
        ]);
    }

    // Formats a raw menu_items DB row into the shape the vendor-admin
    // frontend (VendorMenuMgmtView.vue) expects. Used by getMenu, addMenuItem,
    // updateMenuItem, and toggleStock so the response shape is always
    // consistent no matter which action triggered it.
    private function formatMenuItem(array $item): array {
        return [
            'id'          => (int)$item['id'],
            'vendorId'    => (int)$item['vendor_id'],
            'name'        => $item['name'],
            'description' => $item['description'],
            'price'       => (float)$item['price'],
            'category'    => $item['category'],
            'isAvailable' => (bool)$item['in_stock'],
            'imageUrl'    => $item['image_url']
        ];
    }

    public function getMenu(Request $req, Response $res): Response
    {
        $userId = (int)$req->getAttribute('userId');

        $vendor = $this->vendors->findVendorByOwnerId($userId);

        if (!$vendor) {
            return $this->json($res, ['error' => 'Vendor not found'], 404);
        }

        $menu = $this->vendors->getMenuItems((int)$vendor['id']);

        $formatted = array_map(fn($item) => $this->formatMenuItem($item), $menu);

        return $this->json($res, $formatted);
    }
    
    public function getOrders(Request $req, Response $res): Response
    {
        $userId = (int)$req->getAttribute('userId');

        $vendor = $this->vendors->findVendorByOwnerId($userId);

        if (!$vendor) {
            return $this->json($res, []);
        }

        $orders = $this->orders->getVendorOrders((int)$vendor['id']);

        return $this->json($res, array_map(function($o) {

            return [
                'id'          => (int)$o['id'],
                'userId'      => (int)$o['user_id'],
                'vendorId'    => (int)$o['vendor_id'],
                'studentName' => $this->orders->getUserName((int)$o['user_id']),
                'status'      => $o['status'],
                'total'       => (float)$o['total'],
                'pickupAt'    => $o['pickup_at'],
                'createdAt'   => $o['created_at'],
                'items'       => array_map(fn($i)=>[
                    'id'         => (int)$i['id'],
                    'menuItemId' => (int)$i['menu_item_id'],
                    'name'       => $i['name'],
                    'quantity'   => (int)$i['qty'],
                    'unitPrice'  => (float)$i['unit_price']
                ], $this->orders->getOrderItems((int)$o['id']))
            ];

        }, $orders));
    }

    public function addMenuItem(Request $req, Response $res): Response{
        $userId = (int)$req->getAttribute('userId');

        $vendor = $this->vendors->findVendorByOwnerId($userId);

        if (!$vendor) {
            return $this->json($res, ['error' => 'Vendor not found'], 404);
        }

        $body = (array)$req->getParsedBody();

        $id = $this->vendors->addMenuItem(
            (int)$vendor['id'],
            $body['name'],
            $body['description'] ?? '',
            (float)$body['price'],
            $body['category'],
            (bool)$body['isAvailable']
        );

        return $this->json(
            $res,
            $this->formatMenuItem($this->vendors->findMenuItem($id)),
            201
        );
    }

    public function updateMenuItem(Request $req, Response $res, array $args): Response{
        $body = (array)$req->getParsedBody();

        $this->vendors->updateMenuItem(
            (int)$args['id'],
            $body['name'],
            $body['description'] ?? '',
            (float)$body['price'],
            $body['category'],
            (bool)$body['isAvailable']
        );

        return $this->json(
            $res,
            $this->formatMenuItem($this->vendors->findMenuItem((int)$args['id']))
        );
    }

    public function deleteMenuItem(Request $req, Response $res, array $args): Response{
        $this->vendors->deleteMenuItem((int)$args['id']);

        return $this->json($res, [
            'success' => true
        ]);
    }

    public function toggleStock(Request $req, Response $res, array $args): Response{
        $item = $this->vendors->toggleStock((int)$args['id']);

        return $this->json($res, $this->formatMenuItem($item));
    }

    public function toggleOpen(Request $req, Response $res, array $args): Response{
        error_log("Vendor ID: " . $args['id']);
        $isOpen = $this->vendors->toggleOpen((int)$args['id']);

        return $this->json($res, [
            'isOpen' => $isOpen
        ]);
    }

    public function updateProfile(Request $req, Response $res, array $args): Response
    {
        $body = (array)$req->getParsedBody();

        $this->vendors->updateProfile(
            (int)$args['id'],
            trim($body['name'] ?? ''),
            trim($body['location'] ?? ''),
            trim($body['openingHours'] ?? '')
        );

        $vendor = $this->vendors->findById((int)$args['id']);

        return $this->json($res, [
            'id'           => (int)$vendor['id'],
            'name'         => $vendor['name'],
            'location'     => $vendor['location'],
            'openingHours' => $vendor['opening_hours'],
            'status'       => $vendor['status'],
            'ownerName'    => $this->orders->getUserName((int)$vendor['owner_id'])
        ]);
    }
}