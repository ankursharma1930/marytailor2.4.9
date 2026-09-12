<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
declare(strict_types=1);

namespace Magefan\BlogAuthor\Plugin\Magefan\Blog\Model;

use Magefan\BlogAuthor\Model\ResourceModel\Author\CollectionFactory;
use Magefan\Blog\Api\AuthorRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class Post
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var AuthorRepositoryInterface
     */
    private $authorRepository;

    /**
     * @var array
     */
    private $relatedCoauthors = [];

    /**
     * @param CollectionFactory $collectionFactory
     * @param AuthorRepositoryInterface $authorRepository
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        AuthorRepositoryInterface $authorRepository = null
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->authorRepository = $authorRepository ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magefan\Blog\Api\AuthorRepositoryInterface::class
        );
    }

    /**
     * @param \Magefan\Blog\Model\Post $post
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundGetRelatedCoauthors(
        \Magefan\Blog\Model\Post $post,
        \Closure $proceed
    ) {
        if (!isset($this->relatedCoauthors[$post->getId()])) {
            if (!$post->getCoauthors()) {
                $this->relatedCoauthors[$post->getId()] = [];
            } else {
                $this->relatedCoauthors[$post->getId()] = [];
                foreach ($post->getCoauthors() as $authorId) {
                    try {
                        $author = $this->authorRepository->getById($authorId);
                        if ($author->getId() && $author->isVisibleOnStore($post->getStoreId())) {
                            $this->relatedCoauthors[$post->getId()][] = $author;
                        }
                    } catch (NoSuchEntityException $e) { }
                }
            }
        }

        return $this->relatedCoauthors[$post->getId()];
    }
}