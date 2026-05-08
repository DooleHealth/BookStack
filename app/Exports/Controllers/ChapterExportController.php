<?php

namespace BookStack\Exports\Controllers;

use BookStack\Entities\Queries\ChapterQueries;
use BookStack\Exceptions\NotFoundException;
use BookStack\Exports\ExportFormatter;
use BookStack\Exports\Jobs\GenerateChapterPdfJob;
use BookStack\Exports\ZipExports\ZipExportBuilder;
use BookStack\Http\Controller;
use BookStack\Permissions\Permission;
use Throwable;

class ChapterExportController extends Controller
{
    public function __construct(
        protected ChapterQueries $queries,
        protected ExportFormatter $exportFormatter,
    ) {
        $this->middleware(Permission::ContentExport->middleware());
        $this->middleware('throttle:exports');
    }

    /**
     * Exports a chapter to pdf.
     *
     * @throws NotFoundException
     * @throws Throwable
     */
    public function pdf(string $bookSlug, string $chapterSlug)
    {
        $chapter = $this->queries->findVisibleBySlugsOrFail($bookSlug, $chapterSlug);
        $pdfContent = $this->exportFormatter->chapterToPdf($chapter);

        return $this->download()->directly($pdfContent, $chapterSlug . '.pdf');
    }

    /**
     * Export a chapter to a self-contained HTML file.
     *
     * @throws NotFoundException
     * @throws Throwable
     */
    public function html(string $bookSlug, string $chapterSlug)
    {
        $chapter = $this->queries->findVisibleBySlugsOrFail($bookSlug, $chapterSlug);
        $containedHtml = $this->exportFormatter->chapterToContainedHtml($chapter);

        return $this->download()->directly($containedHtml, $chapterSlug . '.html');
    }

    /**
     * Export a chapter to a simple plaintext .txt file.
     *
     * @throws NotFoundException
     */
    public function plainText(string $bookSlug, string $chapterSlug)
    {
        $chapter = $this->queries->findVisibleBySlugsOrFail($bookSlug, $chapterSlug);
        $chapterText = $this->exportFormatter->chapterToPlainText($chapter);

        return $this->download()->directly($chapterText, $chapterSlug . '.txt');
    }

    /**
     * Export a chapter to a simple markdown file.
     *
     * @throws NotFoundException
     */
    public function markdown(string $bookSlug, string $chapterSlug)
    {
        $chapter = $this->queries->findVisibleBySlugsOrFail($bookSlug, $chapterSlug);
        $chapterText = $this->exportFormatter->chapterToMarkdown($chapter);

        return $this->download()->directly($chapterText, $chapterSlug . '.md');
    }

    /**
     * Export a book to a contained ZIP export file.
     * @throws NotFoundException
     */
    public function zip(string $bookSlug, string $chapterSlug, ZipExportBuilder $builder)
    {
        $chapter = $this->queries->findVisibleBySlugsOrFail($bookSlug, $chapterSlug);
        $zip = $builder->buildForChapter($chapter);

        return $this->download()->streamedFileDirectly($zip, $chapterSlug . '.zip', true);
    }

    /**
     * Queue a chapter PDF export to be sent via email.
     */
    public function pdfEmail(string $bookSlug, string $chapterSlug)
    {
        try {
            $chapter = $this->queries->findVisibleBySlugsOrFail($bookSlug, $chapterSlug);
            $user = user();

            GenerateChapterPdfJob::dispatch($chapter, $user, app()->getLocale());

            $this->showSuccessNotification(trans('entities.export_pdf_generating', ['name' => $chapter->name]));

            return redirect($chapter->getUrl());
        } catch (\Throwable $th) {
            $this->showErrorNotification(trans('entities.export_pdf_failed', ['message' => $th->getMessage()]));

            return redirect()->back();
        }
    }
}
