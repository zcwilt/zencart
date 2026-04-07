<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\GeoZones;

use Zencart\DbRepositories\GeoZonesRepository;
use Zencart\Request\Request;

class GeoZonesController
{
    protected array $infoBox = ['header' => [], 'content' => []];
    protected array $listingRows = [];
    protected ?object $selectedGeoZone = null;
    protected ?object $selectedSubZone = null;
    protected ?object $splitResults = null;
    protected int $queryNumRows = 0;
    protected string $action = '';
    protected string $saction = '';
    protected int $zoneId = 0;
    protected int $subZoneId = 0;
    protected int $zonePage = 1;
    protected int $subZonePage = 1;

    public function __construct(
        protected Request $request,
        protected $messageStack,
        protected GeoZonesRepository $geoZonesRepository
    ) {
    }

    public function processRequest(): void
    {
        $this->normalizeRequest();
        $this->processSubZoneAction();
        $this->processZoneAction();
        $this->buildListingState();
        $this->buildInfoBox();
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getSubAction(): string
    {
        return $this->saction;
    }

    public function isDetailMode(): bool
    {
        return $this->action === 'list';
    }

    public function currentZoneId(): int
    {
        return $this->zoneId;
    }

    public function currentSubZoneId(): int
    {
        return $this->subZoneId;
    }

    public function currentZonePage(): int
    {
        return $this->zonePage;
    }

    public function currentSubZonePage(): int
    {
        return $this->subZonePage;
    }

    public function currentZoneName(): string
    {
        $zoneId = $this->currentZoneId();
        if ($zoneId === 0) {
            return '';
        }

        return (string) zen_get_geo_zone_name($zoneId);
    }

    public function showZoneScript(): bool
    {
        return $this->currentZoneId() > 0 && in_array($this->saction, ['new', 'edit'], true);
    }

    public function listingRows(): array
    {
        return $this->listingRows;
    }

    public function selectedGeoZone(): ?object
    {
        return $this->selectedGeoZone;
    }

    public function selectedSubZone(): ?object
    {
        return $this->selectedSubZone;
    }

    public function splitResults(): ?object
    {
        return $this->splitResults;
    }

    public function queryNumRows(): int
    {
        return $this->queryNumRows;
    }

    public function getBoxHeader(): array
    {
        return $this->infoBox['header'];
    }

    public function getBoxContent(): array
    {
        return $this->infoBox['content'];
    }

    public function detailPaginationParameters(): string
    {
        return $this->buildQueryString($this->subZoneParams([
            'spage' => null,
        ]));
    }

    public function topLevelPaginationParameters(): string
    {
        return $this->buildQueryString([]);
    }

    public function backToZoneListUrl(): string
    {
        return $this->buildUrl($this->geoZoneParams());
    }

    public function newSubZoneUrl(): string
    {
        $params = $this->subZoneParams([
            'saction' => 'new',
        ]);

        if ($this->selectedSubZone !== null) {
            $params['sID'] = (int) $this->selectedSubZone->association_id;
        }

        return $this->buildUrl($params);
    }

    public function newGeoZoneUrl(): string
    {
        $zoneId = $this->selectedGeoZone !== null
            ? (int) $this->selectedGeoZone->geo_zone_id
            : $this->currentZoneId();

        return $this->buildUrl($this->geoZoneParams($zoneId, 'new_zone'));
    }

    protected function normalizeRequest(): void
    {
        $this->zoneId = max(0, $this->request->integer('zID', 0));
        $this->subZoneId = max(0, $this->request->integer('sID', 0));
        $this->zonePage = max(1, $this->request->integer('zpage', 1));
        $this->subZonePage = max(1, $this->request->integer('spage', 1));
        $this->action = $this->request->string('action', '');
        $this->saction = $this->request->string('saction', '');
    }

    protected function processSubZoneAction(): void
    {
        $saction = $this->saction;
        if ($saction === '') {
            return;
        }

        switch ($saction) {
            case 'insert_sub':
                $zID = (int) zen_db_prepare_input($this->currentZoneId());
                $zoneCountryId = (int) zen_db_prepare_input($this->request->post('zone_country_id', 0));
                $zoneId = (int) zen_db_prepare_input($this->request->post('zone_id', 0));

                $newSubZoneId = $this->geoZonesRepository->insertSubZone($zoneCountryId, $zoneId, $zID);
                zen_redirect($this->buildUrl($this->subZoneParams([
                    'zID' => $zID,
                    'sID' => $newSubZoneId,
                ])));
                break;

            case 'save_sub':
                $sID = (int) zen_db_prepare_input($this->currentSubZoneId());
                $zID = (int) zen_db_prepare_input($this->currentZoneId());
                $zoneCountryId = (int) zen_db_prepare_input($this->request->post('zone_country_id', 0));
                $zoneId = zen_db_prepare_input($this->request->post('zone_id', 0));

                $this->geoZonesRepository->updateSubZone($sID, $zID, $zoneCountryId, $zoneId);

                zen_redirect($this->buildUrl($this->subZoneParams([
                    'zID' => $zID,
                    'sID' => $sID,
                ])));
                break;

            case 'deleteconfirm_sub':
                $sID = (int) zen_db_prepare_input($this->request->post('sID', 0));
                $this->geoZonesRepository->deleteSubZone($sID);
                zen_redirect($this->buildUrl($this->subZoneParams()));
                break;
        }
    }

    protected function processZoneAction(): void
    {
        $action = $this->action;
        if ($action === '') {
            return;
        }

        switch ($action) {
            case 'insert_zone':
                $geoZoneName = zen_db_prepare_input($this->request->post('geo_zone_name', ''));
                $geoZoneDescription = zen_db_prepare_input($this->request->post('geo_zone_description', ''));

                zen_redirect($this->buildUrl($this->geoZoneParams(
                    $this->geoZonesRepository->insertGeoZone($geoZoneName, $geoZoneDescription)
                )));
                break;

            case 'save_zone':
                $zID = (int) zen_db_prepare_input($this->currentZoneId());
                $geoZoneName = zen_db_prepare_input($this->request->post('geo_zone_name', ''));
                $geoZoneDescription = zen_db_prepare_input($this->request->post('geo_zone_description', ''));

                $this->geoZonesRepository->updateGeoZone($zID, $geoZoneName, $geoZoneDescription);

                zen_redirect($this->buildUrl($this->geoZoneParams($zID)));
                break;

            case 'deleteconfirm_zone':
                $zID = (int) zen_db_prepare_input($this->request->post('zID', 0));

                if ($this->geoZonesRepository->geoZoneHasTaxRates($zID)) {
                    $this->messageStack->add_session(ERROR_TAX_RATE_EXISTS, 'caution');
                } else {
                    $this->geoZonesRepository->deleteGeoZone($zID);
                }

                zen_redirect($this->buildUrl($this->geoZoneParams()));
                break;
        }
    }

    protected function buildListingState(): void
    {
        if ($this->isDetailMode()) {
            $this->buildSubZoneListingState();
            return;
        }

        $this->buildGeoZoneListingState();
    }

    protected function buildSubZoneListingState(): void
    {
        $zoneId = $this->currentZoneId();
        $query = $this->geoZonesRepository->subZoneListingQuery($zoneId);
        $zoneRows = $this->geoZonesRepository->fetchRows($query);

        if (($this->currentSubZonePage() === 1) && $this->currentSubZoneId() > 0) {
            $checkCount = 0;
            if (count($zoneRows) > MAX_DISPLAY_SEARCH_RESULTS) {
                foreach ($zoneRows as $item) {
                    if ((int) ($item['association_id'] ?? $item->association_id ?? 0) === $this->currentSubZoneId()) {
                        break;
                    }
                    $checkCount++;
                }
                $this->subZonePage = (int) round((($checkCount / MAX_DISPLAY_SEARCH_RESULTS) + (fmod_round($checkCount, MAX_DISPLAY_SEARCH_RESULTS) != 0 ? .5 : 0)), 0);
            } else {
                $this->subZonePage = 1;
            }
        }

        $currentPage = $this->currentSubZonePage();
        $queryNumRows = 0;
        $this->splitResults = $this->geoZonesRepository->paginateQuery($currentPage, $query, $queryNumRows);
        $this->queryNumRows = $queryNumRows;

        $this->listingRows = [];
        foreach ($zoneRows as $zone) {
            if (($this->currentSubZoneId() === 0 || $this->currentSubZoneId() === (int) ($zone['association_id'] ?? $zone->association_id ?? 0))
                && $this->selectedSubZone === null
                && substr($this->action, 0, 3) !== 'new') {
                $this->selectedSubZone = new \objectInfo($zone);
            }

            $associationId = (int) ($zone['association_id'] ?? $zone->association_id ?? 0);
            $isSelected = $this->selectedSubZone !== null && $associationId === (int) $this->selectedSubZone->association_id;

            $this->listingRows[] = [
                'association_id' => $associationId,
                'countries_name' => ($zone['countries_name'] ?? $zone->countries_name ?? '') ?: TEXT_ALL_COUNTRIES,
                'zone_name' => ((int) ($zone['zone_id'] ?? $zone->zone_id ?? 0) !== 0) ? (string) ($zone['zone_name'] ?? $zone->zone_name ?? '') : TEXT_ALL_ZONES,
                'selected' => $isSelected,
                'rowLink' => $isSelected
                    ? $this->buildUrl($this->subZoneParams(['zID' => $zoneId, 'sID' => $associationId, 'saction' => 'edit']))
                    : $this->buildUrl($this->subZoneParams(['zID' => $zoneId, 'sID' => $associationId])),
                'infoLink' => $this->buildUrl($this->subZoneParams(['zID' => $zoneId, 'sID' => $associationId])),
            ];
        }
    }

    protected function buildGeoZoneListingState(): void
    {
        $query = $this->geoZonesRepository->geoZoneListingQuery();
        $zones = $this->geoZonesRepository->fetchRows($query);

        if (($this->currentZonePage() === 1) && $this->currentZoneId() > 0) {
            $checkCount = 0;
            if (count($zones) > MAX_DISPLAY_SEARCH_RESULTS) {
                foreach ($zones as $item) {
                    if ((int) ($item['geo_zone_id'] ?? $item->geo_zone_id ?? 0) === $this->currentZoneId()) {
                        break;
                    }
                    $checkCount++;
                }
                $this->zonePage = (int) round((($checkCount / MAX_DISPLAY_SEARCH_RESULTS) + (fmod_round($checkCount, MAX_DISPLAY_SEARCH_RESULTS) != 0 ? .5 : 0)), 0);
            } else {
                $this->zonePage = 1;
            }
        }

        $currentPage = $this->currentZonePage();
        $queryNumRows = 0;
        $this->splitResults = $this->geoZonesRepository->paginateQuery($currentPage, $query, $queryNumRows);
        $this->queryNumRows = $queryNumRows;

        $this->listingRows = [];
        foreach ($zones as $zone) {
            $geoZoneId = (int) ($zone['geo_zone_id'] ?? $zone->geo_zone_id ?? 0);
            $zone = array_merge($zone, $this->geoZonesRepository->geoZoneCounts($geoZoneId));

            if (($this->currentZoneId() === 0 || $this->currentZoneId() === $geoZoneId)
                && $this->selectedGeoZone === null
                && substr($this->action, 0, 3) !== 'new') {
                $this->selectedGeoZone = new \objectInfo($zone);
            }

            $isSelected = $this->selectedGeoZone !== null && $geoZoneId === (int) $this->selectedGeoZone->geo_zone_id;
            $statusIcon = 'status-red';
            if ((int) $zone['num_tax_rates'] > 0 && (int) $zone['num_zones'] > 0) {
                $statusIcon = 'status-green';
            } elseif ((int) $zone['num_zones'] > 0) {
                $statusIcon = 'status-yellow';
            }

            $this->listingRows[] = [
                'geo_zone_id' => $geoZoneId,
                'geo_zone_name' => (string) ($zone['geo_zone_name'] ?? $zone->geo_zone_name ?? ''),
                'geo_zone_description' => (string) ($zone['geo_zone_description'] ?? $zone->geo_zone_description ?? ''),
                'num_zones' => (int) $zone['num_zones'],
                'num_tax_rates' => (int) $zone['num_tax_rates'],
                'selected' => $isSelected,
                'status_icon' => $statusIcon,
                'rowLink' => $isSelected
                    ? $this->buildUrl($this->geoZoneParams($geoZoneId, 'list'))
                    : $this->buildUrl($this->geoZoneParams($geoZoneId)),
                'infoLink' => $this->buildUrl($this->geoZoneParams($geoZoneId)),
                'folderLink' => $this->buildUrl($this->geoZoneParams($geoZoneId, 'list')),
            ];
        }
    }

    protected function buildInfoBox(): void
    {
        if ($this->isDetailMode()) {
            $this->buildSubZoneInfoBox();
            return;
        }

        $this->buildGeoZoneInfoBox();
    }

    protected function buildSubZoneInfoBox(): void
    {
        $zoneId = $this->currentZoneId();

        switch ($this->saction) {
            case 'new':
                $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_NEW_SUB_ZONE . '</h4>');
                $this->setBoxForm(zen_draw_form('zones', FILENAME_GEO_ZONES, $this->buildQueryString($this->subZoneParams([
                    'sID' => $this->currentSubZoneId() > 0 ? $this->currentSubZoneId() : null,
                    'saction' => 'insert_sub',
                ])), 'post', 'class="form-horizontal"'));
                $this->setBoxContent(TEXT_INFO_NEW_SUB_ZONE_INTRO);
                $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY, 'zone_country_id', 'class="control-label"') . zen_draw_pull_down_menu('zone_country_id', zen_get_countries_for_admin_pulldown(TEXT_ALL_COUNTRIES), '', 'onChange="update_zone(this.form);" class="form-control"'));
                $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_ZONE, 'zone_id', 'class="control-label"') . zen_draw_pull_down_menu('zone_id', zen_prepare_country_zones_pull_down(), '', 'class="form-control"'));
                $this->setBoxContent('<br><button type="submit" class="btn btn-primary">' . IMAGE_INSERT . '</button> <a href="' . $this->buildUrl($this->subZoneParams([
                    'sID' => $this->currentSubZoneId() > 0 ? $this->currentSubZoneId() : null,
                ])) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
                break;

            case 'edit':
                if ($this->selectedSubZone === null) {
                    return;
                }

                $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_EDIT_SUB_ZONE . '</h4>');
                $this->setBoxForm(zen_draw_form('zones', FILENAME_GEO_ZONES, $this->buildQueryString($this->subZoneParams([
                    'sID' => (int) $this->selectedSubZone->association_id,
                    'saction' => 'save_sub',
                ])), 'post', 'class="form-horizontal"'));
                $this->setBoxContent(TEXT_INFO_EDIT_SUB_ZONE_INTRO);
                $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY, 'zone_country_id', 'class="control-label"') . zen_draw_pull_down_menu('zone_country_id', zen_get_countries_for_admin_pulldown(TEXT_ALL_COUNTRIES), $this->selectedSubZone->zone_country_id, 'onChange="update_zone(this.form);" class="form-control"'));
                $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_ZONE, 'zone_id', 'class="control-label"') . zen_draw_pull_down_menu('zone_id', zen_prepare_country_zones_pull_down($this->selectedSubZone->zone_country_id), $this->selectedSubZone->zone_id, 'class="form-control"'));
                $this->setBoxContent('<br><button type="submit" class="btn btn-primary">' . IMAGE_UPDATE . '</button> <a href="' . $this->buildUrl($this->subZoneParams([
                    'sID' => (int) $this->selectedSubZone->association_id,
                ])) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
                break;

            case 'delete':
                if ($this->selectedSubZone === null) {
                    return;
                }

                $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_DELETE_SUB_ZONE . '</h4>');
                $this->setBoxForm(zen_draw_form('zones', FILENAME_GEO_ZONES, $this->buildQueryString($this->subZoneParams([
                    'saction' => 'deleteconfirm_sub',
                    'sID' => null,
                ]))) . zen_draw_hidden_field('sID', $this->selectedSubZone->association_id));
                $this->setBoxContent(TEXT_INFO_DELETE_SUB_ZONE_INTRO);
                $this->setBoxContent('<br><b>' . $this->selectedSubZone->countries_name . '</b>');
                $this->setBoxContent('<br><button type="submit" class="btn btn-danger">' . IMAGE_DELETE . '</button> <a href="' . $this->buildUrl($this->subZoneParams([
                    'sID' => (int) $this->selectedSubZone->association_id,
                ])) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
                break;

            default:
                if ($this->selectedSubZone === null) {
                    return;
                }

                $this->setBoxHeader('<h4>' . $this->selectedSubZone->countries_name . '</h4>');
                $this->setBoxContent('<a href="' . $this->buildUrl($this->subZoneParams([
                    'sID' => (int) $this->selectedSubZone->association_id,
                    'saction' => 'edit',
                ])) . '" class="btn btn-primary" role="button">' . IMAGE_EDIT . '</a> <a href="' . $this->buildUrl($this->subZoneParams([
                    'sID' => (int) $this->selectedSubZone->association_id,
                    'saction' => 'delete',
                ])) . '" class="btn btn-warning" role="button">' . IMAGE_DELETE . '</a>', ['align' => 'text-center']);
                $this->setBoxContent('<br>' . TEXT_INFO_DATE_ADDED . ' ' . zen_date_short($this->selectedSubZone->date_added));
                if (!empty($this->selectedSubZone->last_modified)) {
                    $this->setBoxContent(TEXT_INFO_LAST_MODIFIED . ' ' . zen_date_short($this->selectedSubZone->last_modified));
                }
                break;
        }
    }

    protected function buildGeoZoneInfoBox(): void
    {
        switch ($this->action) {
            case 'new_zone':
                $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_NEW_ZONE . '</h4>');
                $this->setBoxForm(zen_draw_form('zones', FILENAME_GEO_ZONES, $this->buildQueryString($this->geoZoneParams($this->currentZoneId(), 'insert_zone')), 'post', 'class="form-horizontal"'));
                $this->setBoxContent(TEXT_INFO_NEW_ZONE_INTRO);
                $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_ZONE_NAME, 'geo_zone_name', 'class="control-label"') . zen_draw_input_field('geo_zone_name', '', zen_set_field_length(TABLE_GEO_ZONES, 'geo_zone_name') . ' class="form-control"'));
                $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_ZONE_DESCRIPTION, 'geo_zone_description', 'class="control-label"') . zen_draw_input_field('geo_zone_description', '', zen_set_field_length(TABLE_GEO_ZONES, 'geo_zone_description') . ' class="form-control"'));
                $this->setBoxContent('<br><button type="submit" class="btn btn-primary">' . IMAGE_INSERT . '</button> <a href="' . $this->buildUrl($this->geoZoneParams($this->currentZoneId())) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
                break;

            case 'edit_zone':
                if ($this->selectedGeoZone === null) {
                    return;
                }

                $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_EDIT_ZONE . '</h4>');
                $this->setBoxForm(zen_draw_form('zones', FILENAME_GEO_ZONES, $this->buildQueryString($this->geoZoneParams((int) $this->selectedGeoZone->geo_zone_id, 'save_zone')), 'post', 'class="form-horizontal"'));
                $this->setBoxContent(TEXT_INFO_EDIT_ZONE_INTRO);
                $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_ZONE_NAME, 'geo_zone_name', 'class="control-label"') . zen_draw_input_field('geo_zone_name', htmlspecialchars($this->selectedGeoZone->geo_zone_name, ENT_COMPAT, CHARSET, true), zen_set_field_length(TABLE_GEO_ZONES, 'geo_zone_name') . ' class="form-control"'));
                $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_ZONE_DESCRIPTION, 'geo_zone_description', 'class="control-label"') . zen_draw_input_field('geo_zone_description', htmlspecialchars($this->selectedGeoZone->geo_zone_description, ENT_COMPAT, CHARSET, true), zen_set_field_length(TABLE_GEO_ZONES, 'geo_zone_description') . ' class="form-control"'));
                $this->setBoxContent('<br><button type="submit" class="btn btn-primary">' . IMAGE_UPDATE . '</button> <a href="' . $this->buildUrl($this->geoZoneParams((int) $this->selectedGeoZone->geo_zone_id)) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
                break;

            case 'delete_zone':
                if ($this->selectedGeoZone === null) {
                    return;
                }

                $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_DELETE_ZONE . '</h4>');
                $this->setBoxForm(zen_draw_form('zones', FILENAME_GEO_ZONES, $this->buildQueryString($this->geoZoneParams(null, 'deleteconfirm_zone'))) . zen_draw_hidden_field('zID', $this->selectedGeoZone->geo_zone_id));
                $this->setBoxContent(TEXT_INFO_DELETE_ZONE_INTRO);
                $this->setBoxContent('<br><b>' . $this->selectedGeoZone->geo_zone_name . '</b>');
                $this->setBoxContent('<br><button type="submit" class="btn btn-danger">' . IMAGE_DELETE . '</button> <a href="' . $this->buildUrl($this->geoZoneParams((int) $this->selectedGeoZone->geo_zone_id)) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
                break;

            default:
                if ($this->selectedGeoZone === null) {
                    return;
                }

                $this->setBoxHeader('<h4>' . $this->selectedGeoZone->geo_zone_name . '</h4>');
                $this->setBoxContent('<a href="' . $this->buildUrl($this->geoZoneParams((int) $this->selectedGeoZone->geo_zone_id, 'edit_zone')) . '" class="btn btn-primary" role="button">' . IMAGE_EDIT . '</a> <a href="' . $this->buildUrl($this->geoZoneParams((int) $this->selectedGeoZone->geo_zone_id, 'delete_zone')) . '" class="btn btn-warning" role="button">' . IMAGE_DELETE . '</a> <a href="' . $this->buildUrl($this->geoZoneParams((int) $this->selectedGeoZone->geo_zone_id, 'list')) . '" class="btn btn-primary" role="button">' . IMAGE_DETAILS . '</a>', ['align' => 'text-center']);
                $this->setBoxContent(((int) $this->selectedGeoZone->num_tax_rates > 0) ? '<a href="' . zen_href_link(FILENAME_TAX_RATES, '', 'NONSSL') . '" class="btn btn-info" role="button">' . IMAGE_TAX_RATES . '</a>' : '', ['align' => 'text-center']);
                $this->setBoxContent('<br>' . TEXT_INFO_NUMBER_ZONES . ' ' . $this->selectedGeoZone->num_zones);
                $this->setBoxContent('<br>' . TEXT_INFO_NUMBER_TAX_RATES . ' ' . $this->selectedGeoZone->num_tax_rates);
                $this->setBoxContent('<br>' . TEXT_INFO_DATE_ADDED . ' ' . zen_date_short($this->selectedGeoZone->date_added));
                if (!empty($this->selectedGeoZone->last_modified)) {
                    $this->setBoxContent(TEXT_INFO_LAST_MODIFIED . ' ' . zen_date_short($this->selectedGeoZone->last_modified));
                }
                $this->setBoxContent('<br>' . TEXT_INFO_ZONE_DESCRIPTION . '<br>' . $this->selectedGeoZone->geo_zone_description);
                break;
        }
    }

    protected function setBoxHeader(string $content, array $params = []): void
    {
        $row = ['text' => $content];
        foreach ($params as $key => $value) {
            $row[$key] = $value;
        }
        $this->infoBox['header'][] = $row;
    }

    protected function setBoxForm(string $content): void
    {
        $this->infoBox['content']['form'] = $content;
    }

    protected function setBoxContent(string $content, array $params = []): void
    {
        $row = ['text' => $content];
        foreach ($params as $key => $value) {
            $row[$key] = $value;
        }
        $this->infoBox['content'][] = $row;
    }

    protected function geoZoneParams(?int $zoneId = null, ?string $action = null, ?int $zonePage = null): array
    {
        return [
            'zpage' => $zonePage ?? $this->currentZonePage(),
            'zID' => $zoneId ?? ($this->currentZoneId() > 0 ? $this->currentZoneId() : null),
            'action' => $action,
        ];
    }

    protected function subZoneParams(array $overrides = []): array
    {
        return array_merge([
            'zpage' => $this->currentZonePage(),
            'zID' => $this->currentZoneId() > 0 ? $this->currentZoneId() : null,
            'action' => 'list',
            'spage' => $this->currentSubZonePage(),
            'sID' => null,
            'saction' => null,
        ], $overrides);
    }

    protected function buildUrl(array $params): string
    {
        return zen_href_link(FILENAME_GEO_ZONES, $this->buildQueryString($params));
    }

    protected function buildQueryString(array $params): string
    {
        $filtered = array_filter(
            $params,
            static fn ($value): bool => $value !== null && $value !== ''
        );

        return http_build_query($filtered);
    }
}
