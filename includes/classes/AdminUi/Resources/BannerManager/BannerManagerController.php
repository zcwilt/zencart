<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\BannerManager;

use DateTime;
use Zencart\DbRepositories\BannerManagerRepository;
use Zencart\Request\Request;

class BannerManagerController
{
    protected array $infoBox = ['header' => [], 'content' => []];
    protected array $listingRows = [];
    protected ?object $selectedBanner = null;
    protected ?object $splitResults = null;
    protected int $queryNumRows = 0;
    protected string $action = '';
    protected string $formAction = 'add';
    protected ?object $formValues = null;
    protected array $groups = [];
    protected int $page = 1;
    protected int $bannerId = 0;

    public function __construct(
        protected Request $request,
        protected $messageStack,
        protected BannerManagerRepository $bannerManagerRepository
    ) {
    }

    public function processRequest(): void
    {
        $this->normalizeRequest();
        $this->processAction();

        if ($this->isFormMode()) {
            $this->buildFormState();
            return;
        }

        $this->buildListingState();
        $this->buildInfoBox();
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function isFormMode(): bool
    {
        return $this->action === 'new';
    }

    public function shouldShowLegend(): bool
    {
        return $this->action === '';
    }

    public function currentPage(): int
    {
        return $this->page;
    }

    public function currentBannerId(): int
    {
        return $this->bannerId;
    }

    public function listingRows(): array
    {
        return $this->listingRows;
    }

    public function splitResults(): ?object
    {
        return $this->splitResults;
    }

    public function queryNumRows(): int
    {
        return $this->queryNumRows;
    }

    public function selectedBanner(): ?object
    {
        return $this->selectedBanner;
    }

    public function getBoxHeader(): array
    {
        return $this->infoBox['header'];
    }

    public function getBoxContent(): array
    {
        return $this->infoBox['content'];
    }

    public function formAction(): string
    {
        return $this->formAction;
    }

    public function formValues(): ?object
    {
        return $this->formValues;
    }

    public function groupOptions(): array
    {
        return $this->groups;
    }

    public function abbreviatedImagesDirectory(): string
    {
        $directory = DIR_FS_CATALOG_IMAGES;
        if (mb_strlen($directory) > 45) {
            return mb_substr($directory, 0, 10) . '...' . mb_substr($directory, -32);
        }

        return $directory;
    }

    public function listingPaginationParameters(): string
    {
        return $this->buildQueryString(['bID' => $this->currentBannerId()]);
    }

    public function newBannerUrl(): string
    {
        return zen_href_link(FILENAME_BANNER_MANAGER, 'action=new');
    }

    public function cancelFormUrl(): string
    {
        $params = [];
        if ($this->page > 1 || $this->request->has('page')) {
            $params['page'] = $this->currentPage();
        }
        if ($this->currentBannerId() > 0) {
            $params['bID'] = $this->currentBannerId();
        }

        return $this->buildUrl($params);
    }

    public function formPostParameters(): string
    {
        $params = [];
        if ($this->page > 1 || $this->request->has('page')) {
            $params['page'] = $this->currentPage();
        }
        $params['action'] = $this->formAction;

        return $this->buildQueryString($params);
    }

    protected function normalizeRequest(): void
    {
        $this->bannerId = max(0, $this->request->integer('bID', 0));
        $this->page = max(1, $this->request->integer('page', 1));
        $this->action = $this->request->string('action', '');
    }

    protected function processAction(): void
    {
        $action = $this->action;
        if ($action === '') {
            return;
        }

        switch ($action) {
            case 'setflag':
                $flag = $this->request->integer('flag', -1);
                if ($flag === 0 || $flag === 1) {
                    zen_set_banner_status($this->currentBannerId(), $flag);
                    $this->messageStack->add_session(SUCCESS_BANNER_STATUS_UPDATED, 'success');
                } else {
                    $this->messageStack->add_session(ERROR_UNKNOWN_STATUS_FLAG, 'error');
                }

                zen_redirect($this->buildUrl([
                    'page' => $this->currentPage(),
                    'bID' => $this->currentBannerId(),
                ]));
                break;

            case 'setbanners_open_new_windows':
                $flag = $this->request->integer('flagbanners_open_new_windows', -1);
                if ($flag === 0 || $flag === 1) {
                    $this->bannerManagerRepository->updateOpenNewWindow($this->currentBannerId(), $flag);
                    $this->messageStack->add_session(SUCCESS_BANNER_OPEN_NEW_WINDOW_UPDATED, 'success');
                } else {
                    $this->messageStack->add_session(ERROR_UNKNOWN_BANNER_OPEN_NEW_WINDOW, 'error');
                }

                zen_redirect($this->buildUrl([
                    'page' => $this->currentPage(),
                    'bID' => $this->currentBannerId(),
                ]));
                break;

            case 'insert':
            case 'update':
                $this->action = ($action === 'insert') ? 'add' : 'upd';
                $action = $this->action;
                // no break

            case 'add':
            case 'upd':
                $this->processSaveAction($action);
                break;

            case 'deleteconfirm':
                $bannersId = (int) zen_db_prepare_input($this->request->post('bID', 0));

                if ($this->request->post('delete_image') === 'on') {
                    $bannerImage = $this->bannerManagerRepository->bannerImageById($bannersId);

                    if ($bannerImage !== null && is_file(DIR_FS_CATALOG_IMAGES . $bannerImage)) {
                        if (is_writeable(DIR_FS_CATALOG_IMAGES . $bannerImage)) {
                            unlink(DIR_FS_CATALOG_IMAGES . $bannerImage);
                        } else {
                            $this->messageStack->add_session(ERROR_IMAGE_IS_NOT_WRITEABLE, 'error');
                        }
                    } else {
                        $this->messageStack->add_session(ERROR_IMAGE_DOES_NOT_EXIST, 'error');
                    }
                }

                $this->bannerManagerRepository->deleteBannerAndHistory($bannersId);
                $this->messageStack->add_session(SUCCESS_BANNER_REMOVED, 'success');

                zen_redirect($this->buildUrl(['page' => $this->currentPage()]));
                break;
        }
    }

    protected function processSaveAction(string $action): void
    {
        $bannersId = (int) $this->request->post('banners_id', 0);
        $bannersTitle = zen_db_prepare_input($this->request->post('banners_title', ''));
        $bannersUrl = zen_db_prepare_input($this->request->post('banners_url', ''));
        $newBannersGroup = zen_db_prepare_input($this->request->post('new_banners_group', ''));
        $bannersGroup = ($newBannersGroup === '') ? zen_db_prepare_input($this->request->post('banners_group', '')) : $newBannersGroup;
        $bannersHtmlText = zen_db_prepare_input($this->request->post('banners_html_text', ''));
        $bannersImageLocal = zen_db_prepare_input($this->request->post('banners_image_local', ''));
        $bannersImageTarget = zen_db_prepare_input($this->request->post('banners_image_target', ''));

        $bannerError = false;
        $uploadedImage = '';

        $expiresDate = $this->prepareDateValue(
            zen_db_prepare_input($this->request->post('expires_date', '')),
            ERROR_INVALID_EXPIRES_DATE,
            $bannerError
        );
        $expiresImpressions = (int) $this->request->post('expires_impressions', 0);
        $dateScheduled = $this->prepareDateValue(
            zen_db_prepare_input($this->request->post('date_scheduled', '')),
            ERROR_INVALID_SCHEDULED_DATE,
            $bannerError
        );

        $status = (int) $this->request->post('status', 0);
        $bannersOpenNewWindows = (int) $this->request->post('banners_open_new_windows', 0);
        $bannersSortOrder = (int) $this->request->post('banners_sort_order', 0);

        if ($bannersTitle === '') {
            $this->messageStack->add(ERROR_BANNER_TITLE_REQUIRED, 'error');
            $bannerError = true;
        }

        if ($bannersGroup === '') {
            $this->messageStack->add(ERROR_BANNER_GROUP_REQUIRED, 'error');
            $bannerError = true;
        }

        $bannersImage = new \upload('banners_image');
        $bannersImage->set_extensions(['jpg', 'jpeg', 'gif', 'png', 'webp', 'flv', 'webm', 'ogg']);
        $bannersImage->set_destination(DIR_FS_CATALOG_IMAGES . $bannersImageTarget);
        $hasUploadedImage = $bannersImage->parse();

        if ($hasUploadedImage) {
            $uploadedImage = (string) $bannersImage->save();
            if ($uploadedImage === '' && $bannersImageLocal === '' && $bannersHtmlText === '') {
                $this->messageStack->add(ERROR_BANNER_IMAGE_REQUIRED, 'error');
                $bannerError = true;
            }
        } elseif ($bannersImageLocal === '' && $bannersHtmlText === '') {
            $this->messageStack->add(ERROR_BANNER_IMAGE_REQUIRED, 'error');
            $bannerError = true;
        }

        $dbImageLocation = $bannersImageLocal;
        if ($uploadedImage !== '') {
            $dbImageLocation = $bannersImageTarget . $bannersImage->filename;
        }

        if ($bannerError) {
            $this->action = 'new';
            if ($bannersId > 0) {
                $this->bannerId = $bannersId;
            }
            return;
        }

        $dbImageLocation = zen_limit_image_filename($dbImageLocation, TABLE_BANNERS, 'banners_image');
        $bannersUrl = zen_limit_image_filename($bannersUrl, TABLE_BANNERS, 'banners_url');

        $sqlDataArray = [
            'banners_title' => $bannersTitle,
            'banners_url' => $bannersUrl,
            'banners_image' => $dbImageLocation,
            'banners_group' => $bannersGroup,
            'banners_html_text' => $bannersHtmlText,
            'status' => $status,
            'banners_open_new_windows' => $bannersOpenNewWindows,
            'banners_sort_order' => $bannersSortOrder,
        ];

        if ($action === 'add') {
            $sqlDataArray['date_added'] = 'now()';
            $sqlDataArray['status'] = '1';
            $bannersId = $this->bannerManagerRepository->insertBanner($sqlDataArray);
            $this->messageStack->add_session(SUCCESS_BANNER_INSERTED, 'success');
        } else {
            $this->bannerManagerRepository->updateBanner($bannersId, $sqlDataArray);
            $this->messageStack->add_session(SUCCESS_BANNER_UPDATED, 'success');
        }

        $this->bannerManagerRepository->updateBannerSchedule($bannersId, $dateScheduled, $expiresDate, $expiresImpressions);

        $params = [];
        if ($this->page > 1 || $this->request->has('page')) {
            $params['page'] = $this->currentPage();
        }
        $params['bID'] = $bannersId;
        zen_redirect($this->buildUrl($params));
    }

    protected function prepareDateValue(string $rawDate, string $errorMessage, bool &$bannerError): string
    {
        if ($rawDate === '') {
            return 'null';
        }

        if (DATE_FORMAT_DATE_PICKER !== 'yy-mm-dd') {
            $localFormat = zen_datepicker_format_fordate();
            $dateTime = DateTime::createFromFormat($localFormat, $rawDate);
            $rawDate = 'null';
            if (!empty($dateTime)) {
                $rawDate = $dateTime->format('Y-m-d');
            }
        }

        if (zcDate::validateDate($rawDate) === true) {
            return $rawDate;
        }

        $bannerError = true;
        $this->messageStack->add($errorMessage, 'error');
        return 'null';
    }

    protected function buildFormState(): void
    {
        $this->formAction = 'add';
        $parameters = [
            'expires_date' => '',
            'date_scheduled' => '',
            'banners_title' => '',
            'banners_url' => '',
            'banners_group' => '',
            'banners_image' => '',
            'banners_html_text' => '',
            'expires_impressions' => '',
            'banners_open_new_windows' => 1,
            'status' => 1,
            'banners_sort_order' => '',
        ];

        $this->formValues = new \objectInfo($parameters);

        if ($this->currentBannerId() > 0) {
            $this->formAction = 'upd';

            $banner = $this->bannerManagerRepository->bannerFormDataById($this->currentBannerId());
            if ($banner !== null) {
                $this->formValues->updateObjectInfo($banner);
            }
        } elseif ($this->request->post() !== []) {
            $this->formValues->updateObjectInfo($this->request->post());
        }

        $this->groups = $this->bannerManagerRepository->bannerGroups();
    }

    protected function buildListingState(): void
    {
        $bannersQueryRaw = $this->bannerManagerRepository->bannerListingQuery();
        $banners = $this->bannerManagerRepository->fetchRows($bannersQueryRaw);

        if ((!$this->request->has('page') || $this->currentPage() === 1) && $this->currentBannerId() > 0) {
            $checkCount = 0;
            if (count($banners) > MAX_DISPLAY_SEARCH_RESULTS) {
                foreach ($banners as $item) {
                    if ((int) $item['banners_id'] === $this->currentBannerId()) {
                        break;
                    }
                    $checkCount++;
                }
                $this->page = (int) round((($checkCount / MAX_DISPLAY_SEARCH_RESULTS) + (fmod_round($checkCount, MAX_DISPLAY_SEARCH_RESULTS) !== 0 ? .5 : 0)));
            } else {
                $this->page = 1;
            }
        }

        $this->splitResults = $this->bannerManagerRepository->paginateQuery($this->currentPage(), $bannersQueryRaw, $this->queryNumRows);

        foreach ($banners as $banner) {
            $info = $this->bannerManagerRepository->bannerHistoryTotals((int) $banner['banners_id']);

            if (($this->currentBannerId() === 0 || $this->currentBannerId() === (int) $banner['banners_id'])
                && $this->selectedBanner === null
                && !str_starts_with($this->action, 'new')
            ) {
                $this->selectedBanner = new \objectInfo(array_merge($banner, $info));
            }

            $positions = $this->bannerManagerRepository->bannerPositions((string) $banner['banners_group']);

            $isSelected = $this->selectedBanner !== null
                && (int) $banner['banners_id'] === (int) $this->selectedBanner->banners_id;

            $this->listingRows[] = [
                'selected' => $isSelected,
                'rowLink' => $isSelected
                    ? $this->buildUrl([
                        'page' => $this->currentPage(),
                        'bID' => (int) $banner['banners_id'],
                        'action' => 'new',
                    ])
                    : $this->buildUrl([
                        'page' => $this->currentPage(),
                        'bID' => (int) $banner['banners_id'],
                    ]),
                'popupLink' => zen_href_link(FILENAME_POPUP_IMAGE, 'banner=' . (int) $banner['banners_id']),
                'title' => $banner['banners_title'],
                'group' => $banner['banners_group'],
                'positions' => $positions,
                'statistics' => (($info['banners_shown'] ?? '0') ?: '0') . ' / ' . (($info['banners_clicked'] ?? '0') ?: '0'),
                'status' => (int) $banner['status'],
                'statusLink' => $this->buildUrl([
                    'page' => $this->currentPage(),
                    'bID' => (int) $banner['banners_id'],
                    'action' => 'setflag',
                    'flag' => ((int) $banner['status'] === 1) ? 0 : 1,
                ]),
                'statusTitle' => ((int) $banner['status'] === 1) ? IMAGE_ICON_STATUS_ON : IMAGE_ICON_STATUS_OFF,
                'openNewWindow' => (int) $banner['banners_open_new_windows'],
                'openWindowLink' => $this->buildUrl([
                    'page' => $this->currentPage(),
                    'bID' => (int) $banner['banners_id'],
                    'action' => 'setbanners_open_new_windows',
                    'flagbanners_open_new_windows' => ((int) $banner['banners_open_new_windows'] === 1) ? 0 : 1,
                ]),
                'sortOrder' => $banner['banners_sort_order'],
                'statisticsLink' => zen_href_link(
                    FILENAME_BANNER_STATISTICS,
                    'page=' . $this->currentPage() . '&bID=' . (int) $banner['banners_id']
                ),
                'infoLink' => $this->buildUrl([
                    'page' => $this->currentPage(),
                    'bID' => (int) $banner['banners_id'],
                ]),
            ];
        }
    }

    protected function buildInfoBox(): void
    {
        if ($this->selectedBanner === null) {
            return;
        }

        switch ($this->action) {
            case 'delete':
            case 'del':
                $this->infoBox['header'][] = ['text' => '<h4>' . $this->selectedBanner->banners_title . '</h4>'];
                $this->infoBox['content'] = [
                    'form' => zen_draw_form('banners', FILENAME_BANNER_MANAGER, 'page=' . $this->currentPage() . '&action=deleteconfirm')
                        . zen_draw_hidden_field('bID', $this->selectedBanner->banners_id),
                ];
                $this->infoBox['content'][] = ['text' => TEXT_INFO_DELETE_INTRO];
                $this->infoBox['content'][] = ['text' => '<b>' . $this->selectedBanner->banners_title . '</b>'];
                if (!empty($this->selectedBanner->banners_image)) {
                    $this->infoBox['content'][] = [
                        'text' => '<div class="checkbox-inline"><label>' . zen_draw_checkbox_field('delete_image', 'on', true) . TEXT_INFO_DELETE_IMAGE . '</label></div>',
                    ];
                }
                $this->infoBox['content'][] = [
                    'align' => 'text-center',
                    'text' => '<button type="submit" class="btn btn-danger">' . IMAGE_DELETE . '</button> <a href="'
                        . $this->buildUrl(['page' => $this->currentPage(), 'bID' => $this->currentBannerId()])
                        . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>',
                ];
                return;
        }

        $this->infoBox['header'][] = ['text' => '<h4>' . $this->selectedBanner->banners_title . '</h4>'];
        $this->infoBox['content'][] = [
            'align' => 'text-center',
            'text' => '<a href="' . $this->buildUrl([
                'page' => $this->currentPage(),
                'bID' => (int) $this->selectedBanner->banners_id,
                'action' => 'new',
            ]) . '" class="btn btn-primary" role="button">' . IMAGE_EDIT . '</a> <a href="'
                . $this->buildUrl([
                    'page' => $this->currentPage(),
                    'bID' => (int) $this->selectedBanner->banners_id,
                    'action' => 'del',
                ]) . '" class="btn btn-warning" role="button">' . IMAGE_DELETE . '</a>',
        ];
        $this->infoBox['content'][] = ['text' => TEXT_BANNERS_DATE_ADDED . ' ' . zen_date_short($this->selectedBanner->date_added)];
        $this->infoBox['content'][] = [
            'align' => 'text-center',
            'text' => '<a href="' . $this->buildUrl([
                'page' => $this->currentPage(),
                'bID' => (int) $this->selectedBanner->banners_id,
            ]) . '" class="btn btn-default" role="button">' . IMAGE_UPDATE . '</a>',
        ];

        $stats = zen_get_banner_data_recent((int) $this->selectedBanner->banners_id, 3);
        $data = [
            [
                'label' => TEXT_BANNERS_BANNER_VIEWS,
                'data' => $stats[0],
                'bars' => ['order' => 1],
            ],
            [
                'label' => TEXT_BANNERS_BANNER_CLICKS,
                'data' => $stats[1],
                'bars' => ['order' => 2],
            ],
        ];
        $settings = [
            'series' => [
                'bars' => [
                    'show' => 'true',
                    'barWidth' => 0.4,
                    'align' => 'center',
                ],
                'lines, points' => [
                    'show' => 'false',
                ],
            ],
            'xaxis' => [
                'tickDecimals' => 0,
                'ticks' => count($stats[0]),
                'tickLength' => 0,
            ],
            'yaxis' => [
                'tickLength' => 0,
            ],
            'colors' => [
                'blue',
                'red',
            ],
        ];
        $this->infoBox['content'][] = [
            'align' => 'center',
            'text' => '<br><div id="banner-infobox" style="width:200px;height:220px;"></div><div class="flot-x-axis"><div class="flot-tick-label">'
                . TEXT_BANNERS_LAST_3_DAYS
                . '</div></div><script>var data = ' . json_encode($data) . ' ;var options = ' . json_encode($settings)
                . ' ;var plot = $("#banner-infobox").plot(data, options).data("plot");</script>',
        ];

        if (!empty($this->selectedBanner->date_scheduled)) {
            $this->infoBox['content'][] = ['text' => sprintf(TEXT_BANNERS_SCHEDULED_AT_DATE, zen_date_short($this->selectedBanner->date_scheduled))];
        }

        if (!empty($this->selectedBanner->expires_date)) {
            $this->infoBox['content'][] = ['text' => sprintf(TEXT_BANNERS_EXPIRES_AT_DATE, zen_date_short($this->selectedBanner->expires_date))];
        } elseif (!empty($this->selectedBanner->expires_impressions)) {
            $this->infoBox['content'][] = ['text' => sprintf(TEXT_BANNERS_EXPIRES_AT_IMPRESSIONS, $this->selectedBanner->expires_impressions)];
        }

        if (!empty($this->selectedBanner->date_status_change)) {
            $this->infoBox['content'][] = ['text' => sprintf(TEXT_BANNERS_STATUS_CHANGE, zen_date_short($this->selectedBanner->date_status_change))];
        }
    }

    protected function buildQueryString(array $params): string
    {
        $query = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query[] = $key . '=' . $value;
        }

        return implode('&', $query);
    }

    protected function buildUrl(array $params): string
    {
        return zen_href_link(FILENAME_BANNER_MANAGER, $this->buildQueryString($params));
    }
}
