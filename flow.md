verify. 
Click email link
    ↓
GET /verify_user?email=...&token=...
    ↓
Verification page loads
    ↓
JavaScript extracts email and token
    ↓
POST /api/verify_user
    ↓
Backend hashes token and checks database
    ↓
User active status becomes 1
    ↓
Token is deleted
    ↓
Success message
    ↓
Redirect to login


