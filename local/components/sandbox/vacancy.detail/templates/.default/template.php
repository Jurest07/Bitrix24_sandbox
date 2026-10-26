<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$this->addExternalCss($this->GetFolder() . '/style.css');

/** @var array $arResult */

$vacancy = $arResult['VACANCY'];
$salary = $vacancy['SALARY'];
?>

<div class="vacancy-detail">

    <!-- ===== ЗАГОЛОВОК ===== -->
    <div class="vacancy-detail__hero">
        <h1 class="vacancy-detail__title"><?= $vacancy['NAME'] ?></h1>

        <div class="vacancy-detail__badges">
            <?php if (!empty($vacancy['SECTION'])): ?>
                <span class="vacancy-detail__badge vacancy-detail__badge--section">
                    <?= $vacancy['SECTION'] ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($vacancy['EMPLOYMENT_TYPE'])): ?>
                <span class="vacancy-detail__badge">
                    <?= $vacancy['EMPLOYMENT_TYPE'] ?>
                </span>
            <?php endif; ?>

            <?php if (!empty($vacancy['EXPERIENCE'])): ?>
                <span class="vacancy-detail__badge">
                    Опыт: <?= $vacancy['EXPERIENCE'] ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== КЛЮЧЕВАЯ ИНФОРМАЦИЯ ===== -->
    <div class="vacancy-detail__key-info">
        <?php if (!empty($vacancy['CITY'])): ?>
            <div class="vacancy-detail__key-item">
                <svg class="vacancy-detail__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                <div class="vacancy-detail__key-content">
                    <span class="vacancy-detail__key-label">Город</span>
                    <span class="vacancy-detail__key-value"><?= $vacancy['CITY'] ?></span>
                </div>
            </div>
        <?php endif; ?>

        <div class="vacancy-detail__key-item vacancy-detail__key-item--salary">
            <svg class="vacancy-detail__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <div class="vacancy-detail__key-content">
                <span class="vacancy-detail__key-label">Зарплата</span>
                <span class="vacancy-detail__key-value"><?= $salary ?></span>
            </div>
        </div>

        <?php if (!empty($vacancy['DATE_CLOSE'])): ?>
            <div class="vacancy-detail__key-item">
                <svg class="vacancy-detail__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <div class="vacancy-detail__key-content">
                    <span class="vacancy-detail__key-label">Актуально до</span>
                    <span class="vacancy-detail__key-value"><?= $vacancy['DATE_CLOSE'] ?></span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== ОПИСАНИЕ ===== -->
    <?php if (!empty($vacancy['PREVIEW_TEXT'])): ?>
        <div class="vacancy-detail__block">
            <h2 class="vacancy-detail__block-title">О вакансии</h2>
            <div class="vacancy-detail__block-content">
                <?= $vacancy['PREVIEW_TEXT'] ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===== ОБЯЗАННОСТИ ===== -->
    <?php if (!empty($vacancy['RESPONSIBILITIES'])): ?>
        <div class="vacancy-detail__block">
            <h2 class="vacancy-detail__block-title">Обязанности</h2>
            <div class="vacancy-detail__block-content">
                <?= $vacancy['RESPONSIBILITIES'] ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===== ТРЕБОВАНИЯ ===== -->
    <?php if (!empty($vacancy['REQUIREMENTS'])): ?>
        <div class="vacancy-detail__block">
            <h2 class="vacancy-detail__block-title">Требования</h2>
            <div class="vacancy-detail__block-content">
                <?= $vacancy['REQUIREMENTS'] ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===== УСЛОВИЯ ===== -->
    <?php if (!empty($vacancy['CONDITIONS'])): ?>
        <div class="vacancy-detail__block">
            <h2 class="vacancy-detail__block-title">Условия</h2>
            <div class="vacancy-detail__block-content">
                <?= $vacancy['CONDITIONS'] ?>
            </div>
        </div>
    <?php endif; ?>

</div>