// Test script to check actual API response structure
const axios = require('axios');

async function testSearchResponse() {
    try {
        // Login first to get token
        console.log('1. Logging in...');
        const loginResponse = await axios.post('http://localhost:8000/api/auth/login', {
            email: 'johndoe@example.com',
            password: 'Vyhd7Y#PjTb7!TA'
        });
        
        console.log('Login response structure:', JSON.stringify(loginResponse.data, null, 2));
        
        if (loginResponse.data && loginResponse.data.token) {
            const token = loginResponse.data.token;
            console.log('✅ Login successful, token obtained');
            
            // Test template search
            console.log('\n2. Testing template search...');
            const templateResponse = await axios.get('http://localhost:8000/api/search/templates?q=test&page=1&limit=5', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Template search response structure:');
            console.log(JSON.stringify(templateResponse.data, null, 2));
            
            // Test global search
            console.log('\n3. Testing global search...');
            const globalResponse = await axios.get('http://localhost:8000/api/search?q=test&page=1&limit=5', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('Global search response structure:');
            console.log(JSON.stringify(globalResponse.data, null, 2));
            
        } else {
            console.log('❌ Login failed - no token in response');
        }
        
    } catch (error) {
        console.error('Error:', error.response ? error.response.data : error.message);
    }
}

testSearchResponse();
