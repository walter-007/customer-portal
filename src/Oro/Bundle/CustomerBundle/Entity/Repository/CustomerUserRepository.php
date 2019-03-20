<?php

namespace Oro\Bundle\CustomerBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailAwareRepository;
use Oro\Bundle\UserBundle\Entity\Repository\AbstractUserRepository;

/**
 * Doctrine repository for Oro\Bundle\CustomerBundle\Entity\CustomerUser entity.
 */
class CustomerUserRepository extends AbstractUserRepository implements EmailAwareRepository
{
    /**
     * {@inheritdoc}
     */
    protected function getQbForFindUserByEmail(string $email, bool $useLowercase): QueryBuilder
    {
        $qb = parent::getQbForFindUserByEmail($email, $useLowercase);
        $qb->andWhere($qb->expr()->eq('u.isGuest', ':isGuest'))
            ->setParameter('isGuest', false);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    protected function getQbForFindLowercaseDuplicatedEmails(int $limit): QueryBuilder
    {
        $qb = parent::getQbForFindLowercaseDuplicatedEmails($limit);
        $qb->andWhere($qb->expr()->eq('u.isGuest', ':isGuest'))
            ->setParameter('isGuest', false);

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryEmailsQb($fullNameQueryPart, array $excludedEmailNames = [], $query = null)
    {
        $qb = $this->createQueryBuilder('cu');

        $qb
            ->select(sprintf('%s AS name', $fullNameQueryPart))
            ->addSelect('cu.id AS entityId, cu.email, o.name AS organization')
            ->orderBy('name')
            ->leftJoin('cu.organization', 'o');

        if ($query) {
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like($fullNameQueryPart, ':query'),
                    $qb->expr()->like('cu.email', ':query')
                ))
                ->setParameter('query', sprintf('%%%s%%', $query));
        }

        if ($excludedEmailNames) {
            $qb
                ->andWhere($qb->expr()->notIn(
                    sprintf(
                        'TRIM(CONCAT(\'"\', %s, \'" <\', CAST(cu.email AS string), \'>|\', CAST(o.name AS string)))',
                        $fullNameQueryPart
                    ),
                    ':excluded_emails'
                ))
                ->setParameter('excluded_emails', array_values($excludedEmailNames));
        }

        return $qb;
    }

    /**
     * {@inheritdoc}
     */
    public function getSecondaryEmailsQb($fullNameQueryPart, array $excludedEmailNames = [], $query = null)
    {
        $qb = $this->createQueryBuilder('cu');

        $qb
            ->select(sprintf('%s AS name', $fullNameQueryPart))
            ->addSelect('cu.email')
            ->addSelect('cu.id AS entityId, e.email, o.name AS organization')
            ->orderBy('name')
            ->leftJoin('cu.organization', 'o');

        if ($query) {
            $qb
                ->andWhere($qb->expr()->orX(
                    $qb->expr()->like($fullNameQueryPart, ':query'),
                    $qb->expr()->like('cu.email', ':query')
                ))
                ->setParameter('query', sprintf('%%%s%%', $query));
        }

        if ($excludedEmailNames) {
            $qb
                ->andWhere($qb->expr()->notIn(
                    sprintf(
                        'TRIM(CONCAT(\'"\', %s, \'" <\', CAST(cu.email AS string), \'>|\', CAST(o.name AS string)))',
                        $fullNameQueryPart
                    ),
                    ':excluded_emails'
                ))
                ->setParameter('excluded_emails', array_values($excludedEmailNames));
        }

        return $qb;
    }
}
