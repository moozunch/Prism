<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleDriveFolderService
{
    private const API_URL =
        'https://www.googleapis.com/drive/v3/files';

    private const FOLDER_MIME =
        'application/vnd.google-apps.folder';

    public function folderForProject(
        Project $project,
        string $accessToken
    ): string {
        $rootFolderId = trim(
            (string) config(
                'services.google.drive.root_folder_id',
                ''
            )
        );

        if ($rootFolderId === '') {
            throw new RuntimeException(
                'Google Drive root folder is not configured.'
            );
        }

        $folderName = 'Project '
            . $project->id
            . ' - '
            . $this->safeName($project->name);

        return $this->findOrCreateFolder(
            $accessToken,
            $rootFolderId,
            $folderName,
            'prism_project_id',
            (string) $project->id
        );
    }

    public function folderForTask(
        Ticket $ticket,
        string $accessToken
    ): string {
        $project = $ticket->project;

        if (!$project instanceof Project) {
            throw new RuntimeException(
                'Attachment task has no valid project.'
            );
        }

        $projectFolderId = $this->folderForProject(
            $project,
            $accessToken
        );

        $folderName = 'Task '
            . $ticket->id
            . ' - '
            . $this->safeName($ticket->name);

        return $this->findOrCreateFolder(
            $accessToken,
            $projectFolderId,
            $folderName,
            'prism_task_id',
            (string) $ticket->id
        );
    }

    private function findOrCreateFolder(
        string $accessToken,
        string $parentId,
        string $folderName,
        string $propertyKey,
        string $propertyValue
    ): string {
        $query = "'"
            . $this->escapeQueryValue($parentId)
            . "' in parents"
            . " and mimeType = '"
            . self::FOLDER_MIME
            . "'"
            . " and trashed = false"
            . " and appProperties has { key='"
            . $this->escapeQueryValue($propertyKey)
            . "' and value='"
            . $this->escapeQueryValue($propertyValue)
            . "' }";

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(30)
            ->get(self::API_URL, [
                'q' => $query,
                'fields' => 'nextPageToken,files(id,name)',
                'pageSize' => 100,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Google Drive folder lookup failed. HTTP '
                . $response->status()
            );
        }

        $folders = $response->json('files');

        if (!is_array($folders)) {
            throw new RuntimeException(
                'Invalid Google Drive folder response.'
            );
        }

        foreach ($folders as $folder) {
            if (
                is_array($folder)
                && is_string($folder['id'] ?? null)
                && $folder['id'] !== ''
            ) {
                return $folder['id'];
            }
        }

        $created = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(30)
            ->post(
                self::API_URL . '?fields=id,name',
                [
                    'name' => $folderName,
                    'mimeType' => self::FOLDER_MIME,
                    'parents' => [$parentId],
                    'appProperties' => [
                        $propertyKey => $propertyValue,
                    ],
                ]
            );

        if (!$created->successful()) {
            throw new RuntimeException(
                'Google Drive folder creation failed. HTTP '
                . $created->status()
            );
        }

        $folderId = $created->json('id');

        if (!is_string($folderId) || $folderId === '') {
            throw new RuntimeException(
                'Google Drive did not return a folder ID.'
            );
        }

        return $folderId;
    }

    private function safeName(?string $name): string
    {
        $name = trim(
            preg_replace('/\s+/u', ' ', $name ?? '')
                ?? ''
        );

        return Str::limit(
            $name !== '' ? $name : 'Untitled',
            80,
            ''
        );
    }

    private function escapeQueryValue(string $value): string
    {
        return str_replace(
            ['\\', "'"],
            ['\\\\', "\\'"],
            $value
        );
    }
}