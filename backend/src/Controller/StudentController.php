<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repositories\VendorRepository;
use App\Repositories\OrderRepository;
use App\Repositories\DisputeRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StudentController {
    private VendorRepository $vendors;
    private OrderRepository $orders;
    private DisputeRepository $disputes;

    public function __construct(VendorRepository $vendors, OrderRepository $orders, DisputeRepository $disputes) {
        $this->vendors = $vendors;
        $this->orders = $orders;
        $this->disputes = $disputes;
    }

    private function json(Response $res, mixed $data, int $status = 200): Response {
        $res->getBody()->write(json_encode($data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function getVendors(Request $req, Response $res): Response {
        $search = $req->getQueryParams()['search'] ?? '';
        $rows = $this->vendors->getAllActive($search);

        $result = [];
        foreach ($rows as $v) {
            $rating = $this->vendors->getAverageRating((int)$v['id']);
            $result[] = [
                'id'           => (int)$v['id'],
                'ownerId'      => (int)$v['owner_id'],
                'ownerName'    => $v['owner_name'],
                'name'         => $v['name'],
                'location'     => $v['location'],
                'openingHours' => $v['opening_hours'],
                'imageUrl'     => $v['image_url'],
                'status'       => $v['status'],
                'rating'       => $rating !== null ? round($rating, 1) : null,
                'totalOrders'  => $this->vendors->getOrderCount((int)$v['id']),
                'createdAt'    => $v['created_at']
            ];
        }
        return $this->json($res, $result);
    }

    public function getVendorDetails(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $v = $this->vendors->findById($id);
        if (!$v) return $this->json($res, ['error' => 'Vendor stall not found'], 404);

        $rating = $this->vendors->getAverageRating($id);
        return $this->json($res, [
            'id'           => (int)$v['id'],
            'ownerId'      => (int)$v['owner_id'],
            'name'         => $v['name'],
            'location'     => $v['location'],
            'openingHours' => $v['opening_hours'],
            'imageUrl'     => $v['image_url'],
            'status'       => $v['status'],
            'isOpen'       => (bool)$v['is_open'],
            'rating'       => $rating !== null ? round($rating, 1) : null,
            'menu'         => array_map(fn($m) => [
                'id'          => (int)$m['id'],
                'name'        => $m['name'],
                'description' => $m['description'],
                'price'       => (float)$m['price'],
                'category'    => $m['category'],
                'imageUrl'    => $m['image_url'],
                'isAvailable' => (bool)$m['in_stock']
            ], $this->vendors->getMenuItems($id))
        ]);
    }

    public function getVendorMenu(Request $req, Response $res, array $args): Response
    {
        $vendorId = (int)$args['id'];

        $menu = $this->vendors->getMenuItems($vendorId);

        $formatted = array_map(function ($item) {
            return [
                'id'          => (int)$item['id'],
                'vendorId'    => (int)$item['vendor_id'],
                'name'        => $item['name'],
                'description' => $item['description'],
                'price'       => (float)$item['price'],
                'category'    => $item['category'],
                'inStock'     => (bool)$item['in_stock'],   // <-- Vue expects this
                'imageUrl'    => $item['image_url']
            ];
        }, $menu);

        return $this->json($res, $formatted);
    }

    public function getVendorsByCategory(Request $req, Response $res, array $args): Response {
        $category = $args['category'] ?? '';
        
        // Valid categories
        $validCategories = ['rice', 'noodles', 'drinks', 'snacks', 'vegetarian', 'other'];
        if (!in_array($category, $validCategories)) {
            return $this->json($res, ['error' => 'Invalid category'], 400);
        }
        
        $rows = $this->vendors->getVendorsByMenuCategory($category);
        
        $result = [];
        foreach ($rows as $v) {
            $rating = $this->vendors->getAverageRating((int)$v['id']);
            $result[] = [
                'id'           => (int)$v['id'],
                'ownerId'      => (int)$v['owner_id'],
                'ownerName'    => $v['owner_name'],
                'name'         => $v['name'],
                'location'     => $v['location'],
                'openingHours' => $v['opening_hours'],
                'imageUrl'     => $v['image_url'],
                'status'       => $v['status'],
                'isOpen'       => (bool)$v['is_open'],
                'rating'       => $rating !== null ? round($rating, 1) : null,
                'totalOrders'  => $this->vendors->getOrderCount((int)$v['id']),
                'createdAt'    => $v['created_at']
            ];
        }
        return $this->json($res, $result);
    }

    public function searchMenuItems(Request $req, Response $res): Response {
    $search = trim($req->getQueryParams()['search'] ?? '');
    if ($search === '') return $this->json($res, []);

    $rows = $this->vendors->searchMenuItems($search);

    return $this->json($res, array_map(fn($i) => [
        'id'          => (int)$i['id'],
        'vendorId'    => (int)$i['vendor_id'],
        'vendorName'  => $i['vendor_name'],
        'name'        => $i['name'],
        'description' => $i['description'],
        'price'       => (float)$i['price'],
        'category'    => $i['category'],
        'imageUrl'    => $i['image_url'],
    ], $rows));
}

    public function getOrders(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        $orders = $this->orders->getStudentOrders($userId);

        return $this->json($res, array_map(function($o) use ($userId) {
            return [
                'id'          => (int)$o['id'],
                'vendorId'    => (int)$o['vendor_id'],
                'vendorName'  => $this->orders->getVendorName((int)$o['vendor_id']),
                'status'      => $o['status'],
                'total'       => (float)$o['total'],
                'pickupTime'  => $o['pickup_at'],
                'createdAt'   => $o['created_at'],
                'hasReview'   => $this->orders->hasReviewForOrder($userId, (int)$o['id']),
                'hasDispute'  => $this->disputes->hasDisputeForOrder((int)$o['id']),
                'items'       => array_map(fn($i) => [
                    'id'         => (int)$i['id'],
                    'menuItemId' => (int)$i['menu_item_id'],
                    'name'       => $i['name'],
                    'qty'        => (int)$i['qty'],
                    'unitPrice'  => (float)$i['unit_price']
                ], $this->orders->getOrderItems((int)$o['id']))
            ];
        }, $orders));
    }

    public function createOrder(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        $body = (array)$req->getParsedBody();
        $vendorId = (int)($body['vendorId'] ?? 0);
        $pickupAt = trim($body['pickupAt'] ?? '');
        $cart = $body['items'] ?? [];

        if (!$vendorId || !$pickupAt || empty($cart)) {
            return $this->json($res, ['error' => 'Missing checkout payload parameters'], 400);
        }

        $total = 0.0;
        $rows = [];
        foreach ($cart as $item) {
            $mi = $this->orders->findMenuItem((int)($item['menuItemId'] ?? 0));
            if (!$mi || (int)$mi['vendor_id'] !== $vendorId) {
                return $this->json($res, ['error' => 'Invalid menu item context selected'], 400);
            }
            $qty = (int)($item['qty'] ?? 1);
            $unitPrice = (float)$mi['price'];
            $total += ($unitPrice * $qty);
            $rows[] = ['menuItemId' => (int)$mi['id'], 'name' => $mi['name'], 'qty' => $qty, 'unitPrice' => $unitPrice];
        }

        $orderId = $this->orders->createOrder($userId, $vendorId, $pickupAt, $total, $rows);

        // Fix frontend v-for undefined tracking property payload bugs by using quantity mapping standardizations 
        return $this->json($res, [
            'id'        => $orderId,
            'userId'    => $userId,
            'vendorId'  => $vendorId,
            'status'    => 'placed',
            'total'     => $total,
            'pickupAt'  => $pickupAt,
            'createdAt' => date('Y-m-d H:i:s'),
            'items'     => array_map(fn($r) => array_merge(['id' => 0], $r), $rows)
        ], 201);
    }

    public function fileDispute(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        $body = (array)$req->getParsedBody();
        $orderId = (int)($body['orderId'] ?? 0);
        $desc = trim($body['description'] ?? '');

        if (!$orderId || !$desc) return $this->json($res, ['error' => 'Order ID and descriptions are required'], 400);

        $order = $this->orders->findById($orderId);
        if (!$order || (int)$order['user_id'] !== $userId) {
            return $this->json($res, ['error' => 'Order resource verification context mismatch'], 403);
        }

        $this->disputes->create($userId, $orderId, $desc);
        return $this->json($res, ['success' => true], 201);
    }

    public function submitReview(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        $body = (array)$req->getParsedBody();
        $vendorId = (int)($body['vendorId'] ?? 0);
        $orderId = (int)($body['orderId'] ?? 0);
        $rating = (int)($body['rating'] ?? 0);
        $comment = trim($body['comment'] ?? '');

        if (!$vendorId || !$orderId || $rating < 1 || $rating > 5) {
            return $this->json($res, ['error' => 'Valid order, vendor and ratings range required (1-5)'], 400);
        }     

        $this->orders->createReview($userId, $vendorId, $orderId, $rating, $comment);
        return $this->json($res, ['success' => true], 201);
    }

    public function getReviews(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        return $this->json($res, array_map(fn($r) => [
            'id'           => (int)$r['id'],
            'vendorId'     => (int)$r['vendor_id'],
            'vendorName'   => $r['vendor_name'],
            'rating'       => (int)$r['rating'],
            'comment'      => $r['comment'],
            'itemsOrdered' => $r['items_ordered'],
            'createdAt'    => $r['created_at']
        ], $this->orders->getStudentReviews($userId)));
    }

    public function getVendorReviews(Request $req, Response $res, array $args): Response
    {
        $vendorId = (int)$args['id'];

        $reviews = $this->orders->getVendorReviews($vendorId);

        $formatted = array_map(function ($review) {
            return [
                'id'        => (int)$review['id'],
                'userId'    => (int)$review['user_id'],
                'userName'  => $review['user_name'],
                'rating'    => (int)$review['rating'],
                'comment'   => $review['comment'],
                'createdAt' => $review['created_at']
            ];
        }, $reviews);

        return $this->json($res, $formatted);
    }

    public function getActiveBanner(Request $req, Response $res): Response
    {
        $banner = $this->orders->getActiveBanner();

        if (!$banner) {
            return $this->json($res, null);
        }

        return $this->json($res, [
            'id' => (int)$banner['id'],
            'title' => $banner['title'],
            'subtitle' => $banner['subtitle'],
            'theme' => $banner['theme'],
            'startTime' => $banner['start_time'],
            'endTime' => $banner['end_time']
        ]);
    }
}