<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Jobs\GerarRelatorioPdfJob;
use App\Models\User;
use App\Services\RelatorioPdfService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class RelatorioPdfController extends Controller
{
    public function inline(Request $request, RelatorioPdfService $service): Response
    {
        $request->validate([
            'de' => ['required', 'date'],
            'ate' => ['required', 'date'],
            'userId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        /** @var User $authUser */
        $authUser = $request->user();
        $userId = $request->integer('userId', $authUser->id);

        // Funcionario can only download own report
        if ($authUser->role !== UserRole::Admin && $userId !== $authUser->id) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $targetUser = $userId === $authUser->id ? $authUser : User::findOrFail($userId);

        $de = CarbonImmutable::parse($request->string('de'));
        $ate = CarbonImmutable::parse($request->string('ate'));

        return $service->inlineIndividualPdf($targetUser, $de, $ate);
    }

    public function async(Request $request): JsonResponse
    {
        $request->validate([
            'de' => ['required', 'date'],
            'ate' => ['required', 'date'],
            'tipo' => ['nullable', 'string', 'in:individual,geral'],
            'userId' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        /** @var User $authUser */
        $authUser = $request->user();
        $tipo = $request->string('tipo', 'individual')->toString();
        $userId = $request->integer('userId', $authUser->id);

        // Only admin can generate geral reports
        if ($tipo === 'geral' && $authUser->role !== UserRole::Admin) {
            abort(Response::HTTP_FORBIDDEN);
        }

        GerarRelatorioPdfJob::dispatch(
            tipo: $tipo,
            userId: $userId,
            requestedBy: $authUser->id,
            de: $request->string('de')->toString(),
            ate: $request->string('ate')->toString(),
        );

        return response()->json([
            'message' => 'O PDF está a ser gerado. Será notificado quando estiver pronto.',
        ]);
    }

    public function download(Request $request, string $filename): Response
    {
        $path = "pdfs/relatorios/{$filename}";

        if (! Storage::exists($path)) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return Storage::download($path);
    }
}
