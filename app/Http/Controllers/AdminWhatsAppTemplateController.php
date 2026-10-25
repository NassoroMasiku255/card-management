<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWhatsAppTemplateController extends Controller
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = WhatsAppTemplate::query();

        if ($request->filled('search')) {
            $search = '%'.$request->search.'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('language', 'like', $search)
                    ->orWhere('category', 'like', $search)
                    ->orWhere('body_text', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $templates = $query->orderBy('name')
            ->orderBy('language')
            ->paginate(25)
            ->appends($request->query());

        $totalCount = WhatsAppTemplate::count();
        $approvedCount = WhatsAppTemplate::where('status', 'APPROVED')->count();
        $languagesCount = WhatsAppTemplate::distinct('language')->count('language');
        $lastSyncedAt = WhatsAppTemplate::max('synced_at');

        return view('admin.templates.index', compact(
            'templates',
            'totalCount',
            'approvedCount',
            'languagesCount',
            'lastSyncedAt'
        ));
    }

    public function sync(Request $request)
    {
        $this->authorizeAdmin();

        try {
            $result = $this->whatsapp->syncTemplates();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'count' => $result['count'],
                ]);
            }

            return redirect()
                ->route('admin.whatsapp-templates.index')
                ->with('success', $result['message']);
        } catch (\Throwable $e) {
            $errorMessage = 'Failed to sync WhatsApp templates: '.$e->getMessage();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 500);
            }

            return redirect()
                ->route('admin.whatsapp-templates.index')
                ->with('error', $errorMessage);
        }
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403, 'Administrator access required.');
    }
}
