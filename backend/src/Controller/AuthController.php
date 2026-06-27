public function stats(Request $r, Response $s): Response {
    $auth   = (array)$r->getAttribute('auth', []);
    $userId = (int)($auth['sub'] ?? 0);

    $stats = $this->users->getStats($userId);

    return $this->json($s, $stats);
}