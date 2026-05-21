<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Node;
use Illuminate\Http\Request;

class NodeController extends Controller
{
    /** GET /api/admin/nodes */
    public function index()
    {
        return response()->json(Node::withCount('servers')->get());
    }

    /** POST /api/admin/nodes */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'ptero_node_id' => 'nullable|integer',
            'location'      => 'nullable|string|max:100',
            'fqdn'          => 'nullable|string|max:255',
            'max_servers'   => 'integer|min:0',
            'is_active'     => 'boolean',
        ]);

        $node = Node::create($data + ['created_at' => now()]);
        AuditLog::record('node.created', 'node', $node->id, ['name' => $node->name]);
        return response()->json($node, 201);
    }

    /** PUT /api/admin/nodes/{id} */
    public function update(Request $request, $id)
    {
        $node = Node::findOrFail($id);
        $data = $request->validate([
            'name'          => 'string|max:100',
            'ptero_node_id' => 'nullable|integer',
            'location'      => 'nullable|string|max:100',
            'fqdn'          => 'nullable|string|max:255',
            'max_servers'   => 'integer|min:0',
            'is_active'     => 'boolean',
        ]);
        $node->update($data);
        AuditLog::record('node.updated', 'node', $node->id, $data);
        return response()->json($node);
    }

    /** DELETE /api/admin/nodes/{id} */
    public function destroy($id)
    {
        $node = Node::findOrFail($id);
        if ($node->servers()->count() > 0) {
            return response()->json(['error' => 'Нельзя удалить ноду — на ней есть серверы.'], 422);
        }
        $node->delete();
        AuditLog::record('node.deleted', 'node', $id);
        return response()->json(['message' => 'Нода удалена']);
    }
}
