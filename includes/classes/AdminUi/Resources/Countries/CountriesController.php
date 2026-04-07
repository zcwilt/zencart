<?php declare(strict_types=1);
/**
 * @copyright Copyright 2003-2026 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 */

namespace Zencart\AdminUi\Resources\Countries;

use Zencart\DbRepositories\CountriesRepository;
use Zencart\ViewBuilders\BaseController;

class CountriesController extends BaseController
{
    public function __construct(
        $request,
        $messageStack,
        $tableDefinition,
        $formatter,
        protected CountriesRepository $countriesRepository
    ) {
        parent::__construct($request, $messageStack, $tableDefinition, $formatter);
    }

    public function shouldShowNewCountryAction(): bool
    {
        return $this->getAction() === '';
    }

    public function newCountryUrl(): string
    {
        $parameters = $this->formatter->getPersistentLinkParameters();
        if ($parameters !== '') {
            $parameters .= '&';
        }

        return zen_href_link(FILENAME_COUNTRIES, $parameters . 'action=new');
    }

    public function statusFormParameters(): string
    {
        $parameters = $this->formatter->getPersistentLinkParameters(['action', 'cID']);
        if ($parameters !== '') {
            $parameters .= '&';
        }

        return $parameters . 'action=setstatus';
    }

    protected function processDefaultAction(): void
    {
        $currentRow = $this->formatter->currentRowFromRequest();
        if ($currentRow === null) {
            return;
        }

        $pageParameter = $this->currentPageParameter();

        $this->setBoxHeader('<h4>' . zen_output_string_protected($currentRow->countries_name) . '</h4>');
        $this->setBoxContent('<a href="' . zen_href_link(FILENAME_COUNTRIES, $pageParameter . 'cID=' . $currentRow->countries_id . '&action=edit') . '" class="btn btn-primary" role="button">' . IMAGE_EDIT . '</a> <a href="' . zen_href_link(FILENAME_COUNTRIES, $pageParameter . 'cID=' . $currentRow->countries_id . '&action=delete') . '" class="btn btn-warning" role="button">' . IMAGE_DELETE . '</a>', ['align' => 'text-center']);
        $this->setBoxContent('<br>' . TEXT_INFO_COUNTRY_NAME . '<br>' . zen_output_string_protected($currentRow->countries_name));
        $this->setBoxContent('<br>' . TEXT_INFO_COUNTRY_CODE_2 . ' ' . $currentRow->countries_iso_code_2);
        $this->setBoxContent('<br>' . TEXT_INFO_COUNTRY_CODE_3 . ' ' . $currentRow->countries_iso_code_3);
        $this->setBoxContent('<br>' . TEXT_INFO_ADDRESS_FORMAT . ' ' . $currentRow->address_format_id);
        $this->setBoxContent('<br>' . TEXT_INFO_COUNTRY_STATUS . ' ' . ((int) $currentRow->status === 0 ? TEXT_NO : TEXT_YES));
    }

    protected function processActionNew(): void
    {
        $pageParameter = $this->currentPageParameter();

        $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_NEW_COUNTRY . '</h4>');
        $this->setBoxForm(zen_draw_form('countries', FILENAME_COUNTRIES, $pageParameter . 'action=insert', 'post', 'class="form-horizontal"'));
        $this->setBoxContent(TEXT_INFO_INSERT_INTRO);
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_NAME, 'countries_name', 'class="control-label"') . zen_draw_input_field('countries_name', '', 'class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_CODE_2, 'countries_iso_code_2', 'class="control-label"') . zen_draw_input_field('countries_iso_code_2', '', 'class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_CODE_3, 'countries_iso_code_3', 'class="control-label"') . zen_draw_input_field('countries_iso_code_3', '', 'class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_ADDRESS_FORMAT, 'address_format_id', 'class="control-label"') . zen_draw_pull_down_menu('address_format_id', zen_get_address_formats(), '', 'class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_STATUS, 'status', 'class="control-label"') . zen_draw_checkbox_field('status', '', true, 'class="form-control"'));
        $this->setBoxContent('<br><button type="submit" class="btn btn-primary">' . IMAGE_INSERT . '</button> <a href="' . zen_href_link(FILENAME_COUNTRIES, rtrim($pageParameter, '&')) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
    }

    protected function processActionEdit(): void
    {
        $currentRow = $this->formatter->currentRowFromRequest();
        if ($currentRow === null) {
            zen_redirect(zen_href_link(FILENAME_COUNTRIES));
        }

        $pageParameter = $this->currentPageParameter();

        $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_EDIT_COUNTRY . '</h4>');
        $this->setBoxForm(zen_draw_form('countries', FILENAME_COUNTRIES, $pageParameter . 'cID=' . $currentRow->countries_id . '&action=save', 'post', 'class="form-horizontal"'));
        $this->setBoxContent(TEXT_INFO_EDIT_INTRO);
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_NAME, 'countries_name', 'class="control-label"') . zen_draw_input_field('countries_name', htmlspecialchars($currentRow->countries_name, ENT_COMPAT, CHARSET, true), 'class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_CODE_2, 'countries_iso_code_2', 'class="control-label"') . zen_draw_input_field('countries_iso_code_2', $currentRow->countries_iso_code_2, 'class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_CODE_3, 'countries_iso_code_3', 'class="control-label"') . zen_draw_input_field('countries_iso_code_3', $currentRow->countries_iso_code_3, 'class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_ADDRESS_FORMAT, 'address_format_id', 'class="control-label"') . zen_draw_pull_down_menu('address_format_id', zen_get_address_formats(), $currentRow->address_format_id, 'class="form-control"'));
        $this->setBoxContent('<br>' . zen_draw_label(TEXT_INFO_COUNTRY_STATUS, 'status', 'class="control-label"') . zen_draw_checkbox_field('status', '', (bool) $currentRow->status));
        $this->setBoxContent('<br><button type="submit" class="btn btn-primary">' . IMAGE_UPDATE . '</button> <a href="' . zen_href_link(FILENAME_COUNTRIES, $pageParameter . 'cID=' . $currentRow->countries_id) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
    }

    protected function processActionDelete(): void
    {
        $currentRow = $this->formatter->currentRowFromRequest();
        if ($currentRow === null) {
            zen_redirect(zen_href_link(FILENAME_COUNTRIES));
        }

        $pageParameter = $this->currentPageParameter();

        $this->setBoxHeader('<h4>' . TEXT_INFO_HEADING_DELETE_COUNTRY . '</h4>');
        $this->setBoxForm(zen_draw_form('countries', FILENAME_COUNTRIES, $pageParameter . 'action=deleteconfirm') . zen_draw_hidden_field('cID', $currentRow->countries_id));
        $this->setBoxContent(TEXT_INFO_DELETE_INTRO);
        $this->setBoxContent('<br><b>' . zen_output_string_protected($currentRow->countries_name) . '</b>');
        $this->setBoxContent('<br><button type="submit" class="btn btn-danger">' . IMAGE_UPDATE . '</button> <a href="' . zen_href_link(FILENAME_COUNTRIES, $pageParameter . 'cID=' . $currentRow->countries_id) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>', ['align' => 'text-center']);
    }

    protected function processActionInsert(): void
    {
        $countriesName = zen_db_prepare_input($this->request->post('countries_name'));
        $countriesIsoCode2 = strtoupper(zen_db_prepare_input($this->request->post('countries_iso_code_2')));
        $countriesIsoCode3 = strtoupper(zen_db_prepare_input($this->request->post('countries_iso_code_3')));
        $addressFormatId = (int) zen_db_prepare_input($this->request->post('address_format_id'));
        $status = $this->request->post('status', '') === 'on' ? 1 : 0;

        $this->countriesRepository->createCountry(
            $countriesName,
            $countriesIsoCode2,
            $countriesIsoCode3,
            (int) $status,
            $addressFormatId
        );
        zen_record_admin_activity('Country added: ' . $countriesIsoCode3, 'info');
        zen_redirect(zen_href_link(FILENAME_COUNTRIES));
    }

    protected function processActionSave(): void
    {
        $countriesId = $this->request->integer('cID');
        $countriesName = zen_db_prepare_input($this->request->post('countries_name'));
        $countriesIsoCode2 = strtoupper(zen_db_prepare_input($this->request->post('countries_iso_code_2')));
        $countriesIsoCode3 = strtoupper(zen_db_prepare_input($this->request->post('countries_iso_code_3')));
        $addressFormatId = (int) zen_db_prepare_input($this->request->post('address_format_id'));
        $status = $this->request->post('status', '') === 'on' ? 1 : 0;
        $pageParameter = $this->currentPageParameter();

        $this->countriesRepository->updateCountry(
            $countriesId,
            $countriesName,
            $countriesIsoCode2,
            $countriesIsoCode3,
            (int) $status,
            $addressFormatId
        );
        zen_record_admin_activity('Country updated: ' . $countriesIsoCode3, 'info');
        zen_redirect(zen_href_link(FILENAME_COUNTRIES, $pageParameter . 'cID=' . $countriesId));
    }

    protected function processActionDeleteconfirm(): void
    {
        $countriesId = (int) $this->request->post('cID', 0);
        $pageParameter = $this->currentPageParameter();
        if (!$this->countriesRepository->isCountryInUse($countriesId)) {
            $this->countriesRepository->deleteCountry($countriesId);
            zen_record_admin_activity('Country deleted: ' . $countriesId, 'warning');
        } else {
            $this->messageStack->add_session(ERROR_COUNTRY_IN_USE, 'error');
        }

        zen_redirect(zen_href_link(FILENAME_COUNTRIES, rtrim($pageParameter, '&')));
    }

    protected function processActionSetstatus(): void
    {
        $countriesId = (int) $this->request->post('current_country', 0);
        $currentStatus = $this->request->post('current_status');
        $pageParameter = $this->currentPageParameter();

        if ($currentStatus === '0' || $currentStatus === '1') {
            $newStatus = $currentStatus === '0' ? 1 : 0;
            $this->countriesRepository->updateStatus($countriesId, $newStatus);
            zen_record_admin_activity('Country with ID number: ' . $countriesId . ' changed status to ' . $newStatus, 'info');
            zen_redirect(zen_href_link(FILENAME_COUNTRIES, $pageParameter . 'cID=' . $countriesId));
        }
    }

    protected function currentPageParameter(): string
    {
        $parameters = $this->formatter->getPersistentLinkParameters([
            $this->tableDefinition->colKeyName(),
            'action',
        ]);

        if ($parameters === '') {
            return '';
        }

        return rtrim($parameters, '&') . '&';
    }
}
