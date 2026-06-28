<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repositories\VendorRepository;
use App\Repositories\DisputeRepository;
use App\Repositories\OrderRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AdminController {
    private VendorRepository $vendors;
    private DisputeRepository $disputes;
    private OrderRepository $orders;

    public function __construct(VendorRepository $vendors, DisputeRepository $disputes, OrderRepository $orders) {
        $this->vendors = $vendors;
        $this->disputes = $disputes;
        $this->orders = $orders;
    }

    private function json(Response $res, mixed $data, int $status = 200): Response {
        $res->getBody()->write(json_encode($data));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function getVendors(Request $req, Response $res): Response {
        $vendors = $this->vendors->getAllForAdmin();
        $result = [];
        foreach ($vendors as $v) {
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

    public function approveVendor(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $this->vendors->updateStatus($id, 'active');
        $v = $this->vendors->findById($id);
        return $this->json($res, [
            'id' => (int)$v['id'], 
            'ownerId' => (int)$v['owner_id'], 
            'ownerName' => $v['owner_name'],
            'name' => $v['name'], 
            'location' => $v['location'], 
            'openingHours' => $v['opening_hours'],
            'imageUrl' => $v['image_url'], 
            'status' => $v['status']
        ]);
    }

    public function getDisputes(Request $req, Response $res): Response {
        return $this->json($res, array_map(fn($d) => [
            'id'           => (int)$d['id'],
            'orderId'      => (int)$d['order_id'],
            'reportedBy'   => (int)$d['reported_by'],
            'reporterName' => $d['reporter_name'],
            'description'  => $d['description'],
            'status'       => $d['status'],
            'resolution'   => $d['resolution'],
            'createdAt'    => $d['created_at']
        ], $this->disputes->getAllWithReporters()));
    }

    public function resolveDispute(Request $req, Response $res, array $args): Response {
        $body = (array)$req->getParsedBody();
        $resolution = trim($body['resolution'] ?? '');
        if (!$resolution) return $this->json($res, ['error' => 'Resolution required'], 400);

        $id = (int)$args['id'];
        $this->disputes->resolve($id, $resolution);
        $d = $this->disputes->findByIdWithReporter($id);

        if (!$d) return $this->json($res, ['error' => 'Dispute not found'], 404);
        return $this->json($res, [
            'id'           => (int)$d['id'],
            'orderId'      => (int)$d['order_id'],
            'reportedBy'   => (int)$d['reported_by'],
            'reporterName' => $d['reporter_name'],
            'description'  => $d['description'],
            'status'       => $d['status'],
            'resolution'   => $d['resolution'],
            'createdAt'    => $d['created_at']
        ]);
    }

    public function getAnalytics(Request $req, Response $res): Response
    {
        $vendors = $this->vendors->getAllForAdmin();
        $orders = $this->orders->getAllOrders();
        $disputes = $this->disputes->getAll();

        $vendorRevenue = [];

        foreach ($vendors as $vendor) {

            $vendorOrders = array_filter($orders, function ($o) use ($vendor) {
                return (int)$o['vendor_id'] === (int)$vendor['id']
                    && $o['status'] === 'collected';
            });

            $revenue = array_sum(array_map(function ($o) {
                return (float)$o['total'];
            }, $vendorOrders));

            $vendorRevenue[] = [
                'vendorId' => (int)$vendor['id'],
                'vendorName' => $vendor['name'],
                'revenue' => $revenue,
                'orderCount' => count($vendorOrders)
            ];
        }

        return $this->json($res, [
            'totalOrders' => count($orders),

            'totalRevenue' => array_sum(array_map(function ($o) {
                return $o['status'] === 'collected'
                    ? (float)$o['total']
                    : 0;
            }, $orders)),

            'activeVendors' => count(array_filter($vendors, function ($v) {
                return $v['status'] === 'active';
            })),

            'openDisputes' => count(array_filter($disputes, function ($d) {
                return $d['status'] === 'open';
            })),

            'vendorRevenue' => $vendorRevenue
        ]);
    }
}