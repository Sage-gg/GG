<?php
// financial_budgeting_pagination.php
// Pagination logic for financial budgeting

function generatePagination($currentPage, $totalPages, $baseUrl, $queryParams = []) {
    if ($totalPages <= 1) {
        return ''; // No pagination needed
    }
    
    $html = '<nav aria-label="Budget Pagination" class="mt-4">
               <ul class="pagination justify-content-center">';
    
    // Build query string for pagination links
    $queryString = '';
    if (!empty($queryParams)) {
        $filteredParams = array_filter($queryParams, function($value) {
            return $value !== '' && $value !== null;
        });
        if (!empty($filteredParams)) {
            $queryString = '&' . http_build_query($filteredParams);
        }
    }
    
    // Determine which pages to show based on requirements
    $showPages = [];
    
    if ($totalPages <= 3) {
        // If total pages is 3 or less, show all pages
        for ($i = 1; $i <= $totalPages; $i++) {
            $showPages[] = $i;
        }
        $showFirst = false;
        $showLast = false;
    } else {
        if ($currentPage <= 2) {
            // First few pages: show 1, 2, 3
            $showPages = [1, 2, 3];
            $showFirst = false;
            $showLast = true;
        } elseif ($currentPage >= $totalPages - 1) {
            // Last few pages: show last 3 pages
            $showPages = [$totalPages - 2, $totalPages - 1, $totalPages];
            $showFirst = true;
            $showLast = false;
        } else {
            // Middle pages: show current page centered with adjacent pages
            $showPages = [$currentPage - 1, $currentPage, $currentPage + 1];
            $showFirst = true;
            $showLast = true;
        }
    }
    
    // First page button (<<)
    if ($showFirst) {
        $html .= '<li class="page-item">
                    <a class="page-link" href="' . $baseUrl . '?page=1' . $queryString . '" aria-label="First">
                      <span aria-hidden="true">&laquo;&laquo;</span>
                    </a>
                  </li>';
    }
    
    // Page number buttons
    foreach ($showPages as $page) {
        if ($page > 0 && $page <= $totalPages) {
            $activeClass = ($page == $currentPage) ? ' active' : '';
            $html .= '<li class="page-item' . $activeClass . '">
                        <a class="page-link" href="' . $baseUrl . '?page=' . $page . $queryString . '">' . $page . '</a>
                      </li>';
        }
    }
    
    // Last page button (>>)
    if ($showLast) {
        $html .= '<li class="page-item">
                    <a class="page-link" href="' . $baseUrl . '?page=' . $totalPages . $queryString . '" aria-label="Last">
                      <span aria-hidden="true">&raquo;&raquo;</span>
                    </a>
                  </li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

function calculatePagination($totalRecords, $recordsPerPage, $currentPage) {
    $totalPages = ceil($totalRecords / $recordsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages)); // Ensure current page is valid
    $offset = ($currentPage - 1) * $recordsPerPage;
    
    return [
        'total_records' => $totalRecords,
        'records_per_page' => $recordsPerPage,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

function getPaginationInfo($currentPage, $recordsPerPage, $totalRecords, $displayedRecords) {
    $startRecord = (($currentPage - 1) * $recordsPerPage) + 1;
    $endRecord = $startRecord + $displayedRecords - 1;
    
    return [
        'start' => $startRecord,
        'end' => $endRecord,
        'total' => $totalRecords
    ];
}
?>
