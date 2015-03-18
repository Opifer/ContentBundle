<?php

namespace Opifer\ContentBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Opifer\ContentBundle\Event\ContentResponseEvent;
use Opifer\ContentBundle\Event\ResponseEvent;
use Opifer\ContentBundle\OpiferContentEvents as Events;

class ContentController extends Controller
{
    /**
     * Index.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function indexAction(Request $request)
    {
        return $this->retrieveContent($request);
    }

    /**
     * Archive.
     *
     * @param Request $request
     *
     * @return null|JsonResponse|Response
     */
    public function archiveAction(Request $request)
    {
        return $this->retrieveContent($request, true);
    }

    /**
     * @param Request $request
     * @param bool    $archive
     *
     * @return null|JsonResponse|Response
     */
    public function retrieveContent(Request $request, $archive = false)
    {
        $event = new ResponseEvent($request);
        $this->get('event_dispatcher')->dispatch(Events::CONTENT_CONTROLLER_INDEX, $event);
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $paginator = $this->get('opifer.content.content_manager')->getPaginatedByRequest($request, $archive);

        $contents = $paginator->getCurrentPageResults();
        $contents = $this->get('jms_serializer')->serialize(iterator_to_array($contents), 'json');

        $data = [
            'results'       => json_decode($contents, true),
            'total_results' => $paginator->getTotalResults(),
        ];

        return new JsonResponse($data);
    }

    /**
     * View.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return JsonResponse
     */
    public function viewAction(Request $request, $id)
    {
        $manager = $this->get('opifer.content.content_manager');
        $content = $manager->getRepository()->find($id);

        $event = new ContentResponseEvent($content, $request);
        $this->get('event_dispatcher')->dispatch(Events::CONTENT_CONTROLLER_VIEW, $event);
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $content = $this->get('jms_serializer')->serialize($content, 'json');

        return new Response($content, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Restore.
     *
     * @param Request $request
     * @param integer $id
     *
     * @return JsonResponse
     */
    public function restoreAction(Request $request, $id)
    {
        $manager = $this->get('opifer.content.content_manager');
        $content = $manager->retrieveContent($id, true);
        if ($content === null) {
            return new JsonResponse(['success' => false]);
        }

        $event = new ContentResponseEvent($content, $request);
        $this->get('event_dispatcher')->dispatch(Events::CONTENT_CONTROLLER_ARCHIVE_RESTORE, $event);
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $manager->restoreContent($content);

        return new JsonResponse(['success' => true ]);
    }

    /**
     * Delete.
     *
     * @param Request $request
     * @param $id
     *
     * @return null|JsonResponse|Response
     */
    public function deleteAction(Request $request, $id)
    {
        $manager = $this->get('opifer.content.content_manager');
        $content = $manager->retrieveContent($id);
        if ($content === null) {
            return new JsonResponse(['success' => false]);
        }

        $event = new ContentResponseEvent($content, $request);
        $this->get('event_dispatcher')->dispatch(Events::CONTENT_CONTROLLER_DELETE, $event);
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $response = $manager->deleteContent($content);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Permanent Delete.
     *
     * @param Request $request
     * @param $id
     *
     * @return null|JsonResponse|Response
     */
    public function permanentlyDeleteAction(Request $request, $id)
    {
        $repository = $this->get('opifer.content.content_manager');
        $content = $repository->retrieveContent($id, true);
        if ($content === null) {
            return new JsonResponse(['success' => false]);
        }

        $event = new ContentResponseEvent($content, $request);
        $this->get('event_dispatcher')->dispatch(Events::CONTENT_CONTROLLER_ARCHIVE_DELETE, $event);
        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $response = $repository->deleteContent($content);

        return new JsonResponse(['success' => true]);
    }
}
