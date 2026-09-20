<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Models\SchoolMcpConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * School Settings → Integrations (MCP connectors).
 *
 * Per-school connector registry surface (plan C.4): admins see connection
 * state per catalog connector, connect via the vendor-registered OAuth route
 * (mcp/{client}/connect — registered from routes/ai.php in live mode), and
 * revoke by flipping status to 'disabled' (never delete — standing rule #3).
 *
 * Only 'slack' is enabled in the catalog for wave-1; the view renders
 * other catalog entries as "not available yet" without connect buttons.
 */
class IntegrationsController extends Controller
{
    public function index()
    {
        $schoolId = Auth::user()->school_id;

        $connectors = SchoolMcpConnector::query()
            ->forSchool($schoolId)
            ->get()
            ->keyBy('connector_type');

        $catalog = collect(config('toshi.mcp_connectors', []))
            ->filter(fn ($entry) => ($entry['enabled'] ?? false))
            ->map(function ($entry, $type) use ($connectors) {
                $row = $connectors->get($type);

                return [
                    'type' => $type,
                    'label' => ucfirst(str_replace('-', ' ', $type)),
                    'enabled' => true,
                    'connected' => $row !== null && $row->isActive(),
                    'team_name' => $row->external_team_name ?? null,
                    'status' => $row->status ?? null,
                    'write_mode' => $row->write_mode ?? ($entry['default_write_mode'] ?? 'deny'),
                    'last_used_at' => $row->last_used_at ?? null,
                    'connected_at' => $row->created_at ?? null,
                ];
            });

        return view('admin.settings.integrations', [
            'connectors' => $catalog->values(),
            // Connect link only renders when the vendor OAuth flow is genuinely
            // usable: live mode AND a first-party client_id configured (Doppler).
            // Otherwise the route would 500 on dynamic-registration absence.
            'slackConnectable' => config('services.slack_mcp.mode') === 'live'
                && filled(config('services.slack_mcp.client_id')),
        ]);
    }

    /**
     * Revoke a connector: status → 'disabled' (never delete — standing rule #3).
     */
    public function disconnect(Request $request, string $type)
    {
        $schoolId = Auth::user()->school_id;

        $connector = SchoolMcpConnector::query()
            ->forSchool($schoolId)
            ->forType($type)
            ->first();

        if ($connector === null) {
            return redirect()
                ->route('admin.settings.integrations')
                ->with('failmessage', 'No connected '.ucfirst($type).' workspace found.');
        }

        $connector->update(['status' => 'disabled']);

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', ucfirst($type).' workspace disconnected. Toshi can no longer reach it.');
    }
}
