<?php

namespace App\Http\Controllers\Admin\Setting;

use App\Http\Controllers\Controller;
use App\Services\Google\GoogleConnectionService;
use App\Services\Google\GoogleConnectorCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * School allow list. This does not read or change anyone's Google token.
 */
class GoogleConnectorAllowController extends Controller
{
    public function update(Request $request, string $connector, GoogleConnectorCatalog $catalog, GoogleConnectionService $connections)
    {
        $user = Auth::user();

        if ($user?->school_id === null || ! $catalog->has($connector)) {
            abort(404);
        }

        $connections->setAllowed((int) $user->school_id, $connector, $request->boolean('allowed'));

        return redirect()
            ->route('admin.settings.integrations')
            ->with('successmessage', $catalog->get($connector)['label'].($request->boolean('allowed') ? ' is allowed.' : ' is turned off.'));
    }
}
