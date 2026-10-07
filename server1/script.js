// HomeFind - Client-side JavaScript
// Server 1: Presentation Tier

// Configuration - UPDATE THIS with your Server 2 IP address
const SERVER2_URL = 'http://YOUR_SERVER2_IP_HERE';

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

// Event Listeners
searchForm.addEventListener('submit', handleSearch);
newSearchBtn.addEventListener('click', showSearchForm);
dismissErrorBtn.addEventListener('click', hideError);

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
    showLoading();
    
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
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * Show loading spinner
 */
function showLoading() {
    searchCard.style.display = 'none';
    resultsContainer.style.display = 'none';
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
