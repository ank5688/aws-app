// HomeFind - Client-side JavaScript
// Server 1: Presentation Tier

// Configuration - UPDATE THIS with your Server 2 IP address
const SERVER2_URL = ''; //Relative URLs: Server 1 proxies /api/ to Server 2

// DOM Elements
const searchForm = document.getElementById('searchForm');
const searchCard = document.querySelector('.search-card');
const resultsContainer = document.getElementById('results');
const resultsContent = document.getElementById('resultsContent');
const loadingDiv = document.getElementById('loading');
const errorDiv = document.getElementById('error');
const errorText = document.querySelector('.error-text');
const newSearchBtn = document.getElementById('newSearch');
const dismissErrorBtn = document.getElementById('dismissError');
const loadingText = document.getElementById('loadingText');

// School district elements
const schoolsBtn = document.getElementById('schoolsBtn');
const schoolResults = document.getElementById('schoolResults');
const schoolResultsContent = document.getElementById('schoolResultsContent');
const closeSchoolsBtn = document.getElementById('closeSchools');

// Event Listeners
searchForm.addEventListener('submit', handleSearch);
newSearchBtn.addEventListener('click', showSearchForm);
dismissErrorBtn.addEventListener('click', hideError);
schoolsBtn.addEventListener('click', handleSchoolSearch);
closeSchoolsBtn.addEventListener('click', showSearchForm);

/**
 * Handle form submission
 */
async function handleSearch(e) {
    e.preventDefault();
    
    // Get form data
    const zipcode = document.getElementById('zipcode').value;
    const income = document.getElementById('income').value;
    const bedrooms = document.getElementById('bedrooms').value;
    const bathrooms = document.getElementById('bathrooms').value;
    
    // Validate inputs
    if (!zipcode || !income || !bedrooms || !bathrooms) {
        showError('Please fill in all fields');
        return;
    }
    
    if (!/^\d{5}$/.test(zipcode)) {
        showError('Please enter a valid 5-digit zip code');
        return;
    }
    
    if (income < 1) {
        showError('Please enter a valid annual income');
        return;
    }
    
    // Build API URL
    const apiUrl = `${SERVER2_URL}/api/search.php?zip=${zipcode}&income=${income}&beds=${bedrooms}&baths=${bathrooms}`;
    
    // Show loading state
    showLoading('Searching homes and calculating affordability...');
    
    try {
        // Call Server 2 API
        const response = await fetch(apiUrl);
        
        if (!response.ok) {
            throw new Error(`Server error: ${response.status}`);
        }
        
        const html = await response.text();
        
        // Display results
        displayResults(html);
        
    } catch (error) {
        console.error('Error fetching results:', error);
        showError('Unable to connect to the server. Please check your connection and try again.');
    } finally {
        hideLoading();
    }
}

/**
 * Handle the "Find School Districts" button.
 * Uses the zip code from the search form and calls Server 2's schools.php,
 * which is reverse-proxied through Server 1 at /api/schools.php.
 */
async function handleSchoolSearch() {
    const zipcode = document.getElementById('zipcode').value;

    // Validate zip code
    if (!zipcode) {
        showError('Please enter a zip code first, then click "Find School Districts".');
        return;
    }
    if (!/^\d{5}$/.test(zipcode)) {
        showError('Please enter a valid 5-digit zip code.');
        return;
    }

    const apiUrl = `${SERVER2_URL}/api/schools.php?zip=${zipcode}`;

    showLoading('Looking up school districts near ' + zipcode + '...');

    try {
        const response = await fetch(apiUrl);

        if (!response.ok) {
            throw new Error(`Server error: ${response.status}`);
        }

        const data = await response.json();

        if (!data.success) {
            showError(data.error || 'Unable to look up school districts for that zip code.');
            return;
        }

        displaySchoolResults(data.data);

    } catch (error) {
        console.error('Error fetching school districts:', error);
        showError('Unable to connect to the server. Please check your connection and try again.');
    } finally {
        hideLoading();
    }
}

/**
 * Render the school district results returned by schools.php.
 */
function displaySchoolResults(data) {
    const districts = data.districts || [];
    let html = '';

    html += '<div class="results-summary">';
    html += '<p><strong>Zip Code:</strong> ' + escapeHtml(data.zip) +
            ' (' + escapeHtml(data.state) + ')</p>';
    html += '<p><strong>Found:</strong> ' + districts.length + ' school district' +
            (districts.length === 1 ? '' : 's') + ' · ' +
            data.school_count + ' school' + (data.school_count === 1 ? '' : 's') + '</p>';
    html += '</div>';

    if (districts.length === 0) {
        html += '<div class="no-results">';
        html += '<h3>No school districts found</h3>';
        html += '<p>The national school directory has no schools recorded with a physical ' +
                'address in zip code ' + escapeHtml(data.zip) + '. Some zip codes ' +
                '(often downtown or PO-box areas) have no schools located directly in them. ' +
                'Try a nearby residential zip code.</p>';
        html += '</div>';
        schoolResultsContent.innerHTML = html;
        revealSchoolResults();
        return;
    }

    districts.forEach(function (d) {
        html += '<div class="district-card">';
        html += '<div class="district-header">';
        html += '<span class="district-name">🏫 ' + escapeHtml(d.district_name) + '</span>';
        html += '<span class="district-count">' + d.school_count + ' school' +
                (d.school_count === 1 ? '' : 's') + '</span>';
        html += '</div>';
        html += '<div class="district-id">District ID (NCES LEAID): ' +
                escapeHtml(d.district_id) + '</div>';
        html += '</div>';
    });

    html += '<p class="data-attribution"><small>Data: Urban Institute Education Data Portal ' +
            '(Common Core of Data) &amp; Zippopotam.us. School districts are those with ' +
            'schools physically located in this zip code.</small></p>';

    schoolResultsContent.innerHTML = html;
    revealSchoolResults();
}

/**
 * Show the school results panel.
 */
function revealSchoolResults() {
    searchCard.style.display = 'none';
    resultsContainer.style.display = 'none';
    schoolResults.style.display = 'block';
    schoolResults.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Minimal HTML escaper for values rendered into the page.
 */
function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * Display search results
 */
function displayResults(html) {
    resultsContent.innerHTML = html;
    
    // Hide search form and show results
    searchCard.style.display = 'none';
    resultsContainer.style.display = 'block';
    
    // Scroll to results
    resultsContainer.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Show search form again
 */
function showSearchForm() {
    searchCard.style.display = 'block';
    resultsContainer.style.display = 'none';
    schoolResults.style.display = 'none';
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Show loading spinner
 * @param {string} message - Optional message to display under the spinner
 */
function showLoading(message) {
    if (loadingText && message) {
        loadingText.textContent = message;
    }
    searchCard.style.display = 'none';
    resultsContainer.style.display = 'none';
    schoolResults.style.display = 'none';
    errorDiv.style.display = 'none';
    loadingDiv.style.display = 'block';
}

/**
 * Hide loading spinner
 */
function hideLoading() {
    loadingDiv.style.display = 'none';
}

/**
 * Show error message
 */
function showError(message) {
    errorText.textContent = message;
    errorDiv.style.display = 'block';
    
    // Scroll to error
    errorDiv.scrollIntoView({ behavior: 'smooth' });
}

/**
 * Hide error message
 */
function hideError() {
    errorDiv.style.display = 'none';
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    return new Intl.NumberFormat('en-US').format(num);
}

// Optional: Add view JSON functionality
function viewJSON() {
    const zipcode = document.getElementById('zipcode').value;
    const income = document.getElementById('income').value;
    const bedrooms = document.getElementById('bedrooms').value;
    const bathrooms = document.getElementById('bathrooms').value;
    
    const jsonUrl = `${SERVER2_URL}/api/search-json.php?zip=${zipcode}&income=${income}&beds=${bedrooms}&baths=${bathrooms}`;
    
    // Open JSON in new tab
    window.open(jsonUrl, '_blank');
}

// Optional: Make viewJSON available globally for button clicks
window.viewJSON = viewJSON;

// Input validation helpers
document.getElementById('zipcode').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').slice(0, 5);
});

document.getElementById('income').addEventListener('input', function(e) {
    if (this.value < 0) this.value = 0;
});

// Console log for debugging
console.log('HomeFind initialized');
console.log('Server 2 URL:', SERVER2_URL);
console.log('Note: Update SERVER2_URL in script.js with your actual Server 2 IP address');
