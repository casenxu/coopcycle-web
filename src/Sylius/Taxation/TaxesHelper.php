<?php

namespace AppBundle\Sylius\Taxation;

use AppBundle\Entity\Sylius\TaxCategory;
use AppBundle\Service\SettingsManager;
use AppBundle\Sylius\Order\AdjustmentInterface;
use AppBundle\Sylius\Order\OrderInterface;
use Doctrine\ORM\Query\Expr;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TaxesHelper
{
    private $translator;

    public function __construct(
        RepositoryInterface $taxRateRepository,
        TranslatorInterface $translator,
        SettingsManager $settingsManager,
        string $country,
        string $locale)
    {
        $this->taxRateRepository = $taxRateRepository;
        $this->translator = $translator;
        $this->settingsManager = $settingsManager;
        $this->country = $country;
        $this->locale = $locale;
    }

    /**
     * @return array
     */
    public function getTaxTotals(OrderInterface $order, bool $itemsOnly = false): array
    {
        $taxRateCodes = [];
        foreach ($order->getItems() as $item) {
            foreach ($item->getAdjustments(AdjustmentInterface::TAX_ADJUSTMENT) as $adj) {
                $taxRateCodes[] = $adj->getOriginCode();
            }
        }

        if (!$itemsOnly) {
            foreach ($order->getAdjustments(AdjustmentInterface::TAX_ADJUSTMENT) as $adj) {
                $taxRateCodes[] = $adj->getOriginCode();
            }
        }

        $taxRateCodes = array_unique($taxRateCodes);
        $taxRates = array_map(
            fn(string $code) => $this->taxRateRepository->findOneByCode($code),
            $taxRateCodes
        );

        $values = [];
        foreach ($taxRates as $taxRate) {

            $taxTotal = $order->getTaxTotalByRate($taxRate);

            if ($taxTotal > 0) {

                $key = sprintf('%s %d%%',
                    $this->translator->trans($taxRate->getName(), [], 'taxation'),
                    ($taxRate->getAmount() * 100)
                );

                if (isset($values[$key])) {
                    $values[$key] += $taxTotal;
                } else {
                    $values[$key] = $taxTotal;
                }
            }
        }

        return $values;
    }

    public function translate($code): string
    {
        $taxRate = $this->taxRateRepository->findOneByCode($code);

        $formatter = new \NumberFormatter($this->locale, \NumberFormatter::PERCENT);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);

        return sprintf('%s %s',
            $this->translator->trans($taxRate->getName(), [], 'taxation'),
            $formatter->format($taxRate->getAmount())
        );
    }

    private static $baseTaxCategories = [
        'BASE_STANDARD',
        'BASE_INTERMEDIARY',
        'BASE_REDUCED',
    ];

    public function getBaseRates()
    {
        $qb = $this->taxRateRepository->createQueryBuilder('r');
        $qb
            ->join(TaxCategory::class, 'c', Expr\Join::WITH, 'r.category = c.id')
            ->andWhere('LOWER(r.country) = LOWER(:country)')
            ->andWhere(
                $qb->expr()->in('c.code', ':codes')
            )
            ->setParameter('country', $this->country)
            ->setParameter('codes', self::$baseTaxCategories)
            ->orderBy('r.amount', 'ASC')
            ;

        return $qb->getQuery()->getResult();
    }

    private static $baseRateCodeCache = [];

    public function getMatchingBaseRateCode($code)
    {
        if (!isset(self::$baseRateCodeCache[$code])) {

            $rate = $this->taxRateRepository->findOneByCode($code);
            $baseRates = $this->getBaseRates();

            foreach ($baseRates as $baseRate) {
                if ($rate->getAmount() === $baseRate->getAmount()) {
                    self::$baseRateCodeCache[$code] = $baseRate->getCode();

                    return self::$baseRateCodeCache[$code];
                }
            }

            self::$baseRateCodeCache[$code] = null;
        }

        return self::$baseRateCodeCache[$code];
    }

    public function getServiceTaxRateCode()
    {
        $subjectToVat = $this->settingsManager->get('subject_to_vat');
        $code = $subjectToVat ? 'SERVICE' : 'SERVICE_TAX_EXEMPT';

        $qb = $this->taxRateRepository->createQueryBuilder('r');
        $qb
            ->join(TaxCategory::class, 'c', Expr\Join::WITH, 'r.category = c.id')
            ->andWhere('r.country = :country')
            ->andWhere('c.code = :code')
            ->setParameter('country', $this->country)
            ->setParameter('code', $code)
            ;

        $rate = $qb->getQuery()->getOneOrNullResult();

        if ($rate) {
            return $rate->getCode();
        }
    }
}
