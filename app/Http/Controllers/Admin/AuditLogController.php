<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\AuditLabel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'aksi' => ['nullable', Rule::in(array_keys(AuditLabel::ACTIONS))],
            'objek' => ['nullable', Rule::in(array_keys(AuditLabel::OBJECTS))],
            'oleh' => ['nullable', 'string', 'max:40'],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $logs = AuditLog::with('user:id,username,role')
            ->when($filters['aksi'] ?? null, fn ($q, $a) => $q->where('action', $a))
            ->when($filters['objek'] ?? null, fn ($q, $o) => $q->where('auditable_type', 'App\\Models\\'.$o))
            ->when($filters['oleh'] ?? null, fn ($q, $u) => $q->whereHas('user', fn ($q) => $q->where('username', 'like', '%'.$u.'%')))
            ->when($filters['dari'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['sampai'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->latest('created_at')->latest('id')
            ->paginate(40)->withQueryString();

        return view('admin.audit.index', ['logs' => $logs, 'filters' => $filters]);
    }
}
