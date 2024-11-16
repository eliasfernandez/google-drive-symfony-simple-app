<?php

namespace App\Controller;

use App\Document\File;
use App\Document\User;
use App\FileAccess\GoogleDrive;
use App\Form\CreateFolderType;
use App\Form\UploadFileType;
use League\OAuth2\Client\Provider\Google;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Controller extends AbstractController
{
    public function __construct(
        private Security $security,
        private GoogleDrive $googleDrive
    ) {
    }

    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('index.html.twig');
    }

    #[Route('/login', name:'login')]
    public function login(Google $google): Response
    {
        return $this->render('login.html.twig', [
            'google_oauth_link' => $google->getAuthorizationUrl()
        ]);
    }

    #[Route('/files/{folderId}/upload', name: 'upload_file_by_folder', methods: ['POST'])]
    #[Route('/files/upload', name: 'upload_file', methods: ['POST'])]
    public function upload(Request $request, ?string $folderId = null): Response
    {
        $tokenData = $this->checkGoogleLogin();
        if (!is_array($tokenData)) {
            return $this->redirectToRoute('login');
        }
        $this->googleDrive->setAccessToken($tokenData);

        $form = $this->createForm(UploadFileType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();

            if ($file->isValid()) {
                $fileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $this->googleDrive->uploadFile(
                    $file->getRealPath(),
                    $fileName,
                    $folderId
                );
                $this->addFlash('success', 'File has been uploaded.');
                return $this->redirectToRoute($folderId ? 'upload_file_by_folder' : 'upload_file', ['folderId' => $folderId]);
            }
        }
        $this->addFlash('warning', 'Something went wrong uploading the file.');
        return $this->redirectToRoute($folderId ? 'upload_file_by_folder' : 'upload_file', ['folderId' => $folderId]);
    }

    #[Route('/mkdir/{folderId}', name:'dir_by_folder', methods: ['POST'])]
    #[Route('/mkdir', name:'dir', methods: ['POST'])]
    public function mkdir(Request $request, string $folderId = null): Response
    {
        $tokenData = $this->checkGoogleLogin();
        if (!is_array($tokenData)) {
            return $this->redirectToRoute('login');
        }
        $form = $this->createForm(CreateFolderType::class);
        $form->handleRequest($request);

        $name = $form->isSubmitted() && $form->isValid() ? $form->get('name')->getData() : null;
        if (!is_string($name) || '' === $name) {
            $this->addFlash('warning', 'Name cannot be empty.');
            return $this->redirect('/files');
        }
        $this->googleDrive->setAccessToken($tokenData);

        if ($this->googleDrive->dirExists($name)) {
            $this->addFlash('warning', 'Folder already exists.');
            return $this->redirectToRoute($folderId ? 'upload_file_by_folder' : 'upload_file', ['folderId' => $folderId]);
        }

        $this->googleDrive->makeDir($name, $folderId);
        return $this->redirectToRoute($folderId ? 'upload_file_by_folder' : 'upload_file', ['folderId' => $folderId]);
    }

    #[Route('/files', name: 'files')]
    #[Route('/files/{folderId}', name:'files_by_folder')]
    public function files(?string $folderId = null): Response
    {
        $tokenData = $this->checkGoogleLogin();
        if (!is_array($tokenData)) {
            return $this->redirectToRoute('login');
        }
        $this->googleDrive->setAccessToken($tokenData);

        $uploadFile = $this->createForm(
            UploadFileType::class,
            null,
            [
                'action' => $this->generateUrl($folderId ? 'upload_file_by_folder' : 'upload_file', ['folderId' => $folderId]),
                'method' => 'POST',
            ]
        );
        $createFolder = $this->createForm(
            CreateFolderType::class,
            null,
            [
                'action' => $this->generateUrl($folderId ? 'dir_by_folder' : 'dir', ['folderId' => $folderId]),
                'method' => 'POST',
            ]
        );


        return $this->render('files.html.twig', [
            'create' => $createFolder,
            'upload' => $uploadFile,
            'folder' => $folderId,
            'files' => array_map(fn (array $file) => new File(
                $file['id'],
                $file['name'],
                $file['mimeType'],
                new \DateTimeImmutable($file['modifiedTime'])
            ), $this->googleDrive->listFiles($folderId))
        ]);
    }


    #[Route('/files/{fileId}/delete', name:'delete_file')]
    public function delete(string $fileId): Response
    {
        $tokenData = $this->checkGoogleLogin();
        if (!is_array($tokenData)) {
            return $this->redirectToRoute('login');
        }
        $this->googleDrive->setAccessToken($tokenData);


        $this->googleDrive->delete($fileId);
        $this->addFlash('success', 'File has been deleted.');
        return $this->redirect('/files');
    }

    #[Route('/auth/google', name:'auth_google')]
    public function google(): void
    {
        throw new \LogicException('All google authentication should be handled in GoogleAuthenticator.');
    }

    private function checkGoogleLogin(): mixed
    {
        $tokenData = null;
        if (($user = $this->security->getUser()) instanceof User) {
            $tokenData = json_decode($user->getLastToken() ?? '', true);
        }
        if (!is_array($tokenData)) {
            $this->addFlash('warning', 'Refresh your login');
            return false;
        }
        if (is_int($tokenData['expires']) && new \DateTimeImmutable(sprintf('@%s', $tokenData['expires'])) < new \DateTime('now')) {
            $this->addFlash('warning', 'Your token is expired, please login again.');
            return false;
        }

        return $tokenData;
    }
}
