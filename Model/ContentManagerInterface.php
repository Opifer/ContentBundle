<?php

namespace Opifer\ContentBundle\Model;

use Symfony\Component\HttpFoundation\Request;

interface ContentManagerInterface
{
    /**
     * Save content.
     *
     * @param ContentInterface $content
     *
     * @return ContentInterface
     */
    public function save(ContentInterface $content);

    /**
     * Remove content.
     *
     * @param array|integer $content
     */
    public function remove($content);

    /**
     * Get paginated items by request.
     *
     * @param Request $request
     * @param $archive
     *
     * @return mixed
     */
    public function getPaginatedByRequest(Request $request, $archive = false);

    /**
     * Find one content item by its slug.
     *
     * @param string $slug
     *
     * @throws \Doctrine\ORM\NoResultException if content is not found
     *
     * @return ContentInterface
     */
    public function findOneBySlug($slug);


    /**
     * Retrieve content and disables the deletedAt filter if necessary
     *
     * @param $id
     * @param bool $archive
     * @return mixed
     */
    public function retrieveContent($id, $archive = false);

    /**
     * Restores content by clearing deletedAt value
     *
     * @param $content
     * @return mixed
     */
    public function restoreContent($content);

    /**
     * Archives or Deletes content.
     *
     * @param $request
     * @param $id
     * @param bool $archive
     * @return mixed
     */
    public function deleteContent($content);
}
