from locust import HttpUser, task, between
import random

class PineusTiluUser(HttpUser):
    """
    Simulates user behavior for Pineus Tilu camping booking application
    """
    wait_time = between(1, 5)  # Wait 1-5 seconds between tasks
    
    def on_start(self):
        """Called when a simulated user starts"""
        print("Starting load test for Pineus Tilu...")
    
    @task(10)
    def view_homepage(self):
        """View homepage - highest weight (most common action)"""
        self.client.get("/", name="Homepage")
    
    @task(8)
    def view_home_authenticated(self):
        """View home page (camping spots listing)"""
        self.client.get("/home", name="View Camping Spots")
    
    @task(5)
    def view_room_details(self):
        """View specific room/camping spot details"""
        room_id = random.randint(1, 8)  # Assuming 8 rooms from seeder
        self.client.get(f"/room/{room_id}", name="View Room Detail")
    
    @task(3)
    def view_login_page(self):
        """View login page"""
        self.client.get("/login", name="Login Page")
    
    @task(3)
    def view_register_page(self):
        """View registration page"""
        self.client.get("/register", name="Register Page")
    
    @task(2)
    def view_bookings(self):
        """View my bookings page (requires auth)"""
        self.client.get("/my-bookings", name="My Bookings")
    
    @task(1)
    def view_metrics(self):
        """View Prometheus metrics endpoint"""
        with self.client.get("/metrics", catch_response=True, name="Metrics Endpoint") as response:
            if response.status_code == 200:
                response.success()
            elif response.status_code == 401 or response.status_code == 302:
                # Redirect to login is expected for unauthenticated users
                response.success()
            else:
                response.failure(f"Got unexpected status code: {response.status_code}")
    
    @task(2)
    def post_login(self):
        """Attempt to login (will fail without valid credentials)"""
        with self.client.post("/login", 
            data={
                "email": "test@example.com",
                "password": "password123"
            },
            catch_response=True,
            name="Login Attempt"
        ) as response:
            # We expect this to fail or redirect, which is fine for load testing
            if response.status_code in [200, 302, 422]:
                response.success()
            else:
                response.failure(f"Unexpected status code: {response.status_code}")
    
    @task(1)
    def view_static_assets(self):
        """Load static assets"""
        assets = [
            "/build/manifest.json",
            "/favicon.ico",
        ]
        asset = random.choice(assets)
        self.client.get(asset, name="Static Asset")


class AdminUser(HttpUser):
    """
    Simulates admin/power user behavior with more booking interactions
    """
    wait_time = between(2, 8)
    weight = 1  # 10% of users will be admin users
    
    @task(5)
    def browse_rooms(self):
        """Browse multiple rooms"""
        for room_id in range(1, 5):
            self.client.get(f"/room/{room_id}", name="Admin Browse Rooms")
    
    @task(3)
    def check_bookings(self):
        """Check bookings multiple times"""
        self.client.get("/my-bookings", name="Admin Check Bookings")
    
    @task(2)
    def view_metrics_detailed(self):
        """View detailed metrics"""
        self.client.get("/metrics", name="Admin View Metrics")


class CasualVisitor(HttpUser):
    """
    Simulates casual visitors who just browse
    """
    wait_time = between(5, 15)
    weight = 3  # 30% of users will be casual visitors
    
    @task(10)
    def just_browse_homepage(self):
        """Just view homepage"""
        self.client.get("/", name="Casual Homepage Visit")
    
    @task(5)
    def browse_one_room(self):
        """View one random room"""
        room_id = random.randint(1, 8)
        self.client.get(f"/room/{room_id}", name="Casual Room View")
    
    @task(2)
    def view_register(self):
        """Consider registering"""
        self.client.get("/register", name="Casual View Register")