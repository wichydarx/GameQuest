<?php

namespace App\Controller\admin;

use App\Entity\Comments;
use App\Form\CommentsType;
use App\Repository\CommentsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/comments')]
class AdminCommentsController extends AbstractController
{
    #[Route('/', name: 'app_admin_comments_index', methods: ['GET'])]
    public function index(CommentsRepository $commentsRepository): Response
    {
        return $this->render('admin/admin_comments/index.html.twig', [
            'comments' => $commentsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_comments_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CommentsRepository $commentsRepository): Response
    {
        $comment = new Comments();
        $form = $this->createForm(CommentsType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentsRepository->save($comment, true);

            return $this->redirectToRoute('app_admin_comments_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin/admin_comments/new.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_comments_show', methods: ['GET'])]
    public function show(Comments $comment): Response
    {
        return $this->render('admin/admin_comments/show.html.twig', [
            'comment' => $comment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_comments_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Comments $comment, CommentsRepository $commentsRepository): Response
    {
        $form = $this->createForm(CommentsType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentsRepository->save($comment, true);

            return $this->redirectToRoute('app_admin_comments_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin/admin_comments/edit.html.twig', [
            'comment' => $comment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_comments_delete', methods: ['POST'])]
    public function delete(Request $request, Comments $comment, CommentsRepository $commentsRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$comment->getId(), $request->request->get('_token'))) {
            $commentsRepository->remove($comment, true);
        }

        return $this->redirectToRoute('app_admin_comments_index', [], Response::HTTP_SEE_OTHER);
    }
}