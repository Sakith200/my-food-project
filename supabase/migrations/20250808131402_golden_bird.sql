-- Create database
CREATE DATABASE IF NOT EXISTS sm_fast_food;
USE sm_fast_food;

-- Create menu_items table
CREATE TABLE IF NOT EXISTS menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    image VARCHAR(500),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_email VARCHAR(255),
    delivery_address TEXT NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    special_instructions TEXT,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES menu_items(id)
);

-- Insert sample menu items
INSERT INTO menu_items (name, price, description, category, image) VALUES
('Cheese Burger', 850.00, 'Juicy beef patty with melted cheese, lettuce, tomato, and special sauce', 'Burgers', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400&h=300&fit=crop'),
('Chicken Kottu', 1200.00, 'Traditional Sri Lankan street food with chicken, vegetables, and roti', 'Local', 'img/KUTTU-PORATTA.jpg'),
('Fish & Chips', 1450.00, 'Crispy battered fish with golden fries and tartar sauce', 'Seafood', 'img/fish-and-chips-plate.jpg'),
('Chicken Fried Rice', 950.00, 'Wok-fried rice with tender chicken pieces and mixed vegetables', 'Rice', 'https://images.unsplash.com/photo-1603133872878-684f208fb84b?w=400&h=300&fit=crop'),
('Margherita Pizza', 1650.00, 'Fresh tomatoes, mozzarella cheese, basil leaves on a crispy crust', 'Pizza', 'https://images.unsplash.com/photo-1604382354936-07c5d9983bd3?w=400&h=300&fit=crop'),
('Submarine', 750.00, 'Sri Lankan style submarine sandwich with chicken, vegetables, and sauce', 'Sandwiches', 'img/istockphoto-175204982-612x612.jpg'),
('Rice & Curry', 680.00, 'Traditional Sri Lankan rice and curry with dhal, vegetables, and chicken', 'Local', 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=400&h=300&fit=crop'),
('BBQ Chicken Pizza', 1850.00, 'Grilled chicken with BBQ sauce, onions, and mozzarella cheese', 'Pizza', 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=400&h=300&fit=crop'),
('Chicken Wings', 1250.00, 'Spicy buffalo chicken wings served with blue cheese dip', 'Appetizers', 'https://images.unsplash.com/photo-1527477396000-e27163b481c2?w=400&h=300&fit=crop'),
('Spaghetti Bolognese', 1350.00, 'Classic Italian pasta with rich meat sauce and parmesan cheese', 'Pasta', 'https://images.unsplash.com/photo-1551892374-ecf8754cf8b0?w=400&h=300&fit=crop'),
('Chicken Shawarma', 950.00, 'Middle Eastern wrap with spiced chicken, vegetables, and garlic sauce', 'Wraps', 'https://images.unsplash.com/photo-1529006557810-274b9b2fc783?w=400&h=300&fit=crop'),
('Deviled Chicken', 1150.00, 'Spicy Sri Lankan style chicken with bell peppers and onions', 'Local', 'https://images.unsplash.com/photo-1562967916-eb82221dfb92?w=400&h=300&fit=crop'),
('Beef Burger', 1050.00, 'Premium beef patty with caramelized onions, lettuce, and special sauce', 'Burgers', 'https://images.unsplash.com/photo-1550547660-d9450f859349?w=400&h=300&fit=crop'),
('Grilled Chicken Salad', 890.00, 'Fresh mixed greens with grilled chicken, cherry tomatoes, and vinaigrette', 'Salads', 'img/images.jpg'),
('Seafood Pasta', 1750.00, 'Creamy pasta with prawns, fish, and mixed seafood', 'Pasta', 'img/one-pot-seafood-pasta-thumb.jpg'),
('Seafood Noodles', 980.00, 'Stir-fried noodles with seafood and vegetables in soy sauce', 'Noodles', 'https://images.unsplash.com/photo-1569718212165-3a8278d5f624?w=400&h=300&fit=crop'),
('Vegetable Kottu', 850.00, 'Vegetarian version of Sri Lankan kottu with mixed vegetables', 'Local', 'img/Pork-Kottu-1-DSC07740-1.jpg'),
('Chocolate Milkshake', 450.00, 'Rich and creamy chocolate milkshake topped with whipped cream', 'Beverages', 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=400&h=300&fit=crop'),
('Pork Submarine', 850.00, 'Toasted submarine bread with spiced pork filling and vegetables', 'Sandwiches', 'img/Philly-Roast-Pork-Sandwich-with-Lee-Kum-Kee®-Sauce-for-Orange-Chicken-Hero-Featured.png'),
('Prawns Curry', 1650.00, 'Spicy Sri Lankan prawns curry with coconut milk and spices', 'Local', 'img/prawn-curry-with-coconut-milk-07.jpg'),
('Club Sandwich', 950.00, 'Triple-decker sandwich with chicken, bacon, lettuce, and tomato', 'Sandwiches', 'https://images.unsplash.com/photo-1567234669003-dce7a7a88821?w=400&h=300&fit=crop'),
('Watalappan', 380.00, 'Traditional Sri Lankan coconut custard dessert with jaggery', 'Desserts', 'img/1caa4bec10f59ab47f7b9bc51e767c8b_Watalappan-Sri-Lanka_11.jpg'),
('Mixed Grill', 2250.00, 'Combination of grilled chicken, beef, and sausages with sides', 'Grills', 'https://images.unsplash.com/photo-1544025162-d76694265947?w=400&h=300&fit=crop'),
('Mango Lassi', 420.00, 'Refreshing yogurt-based drink with fresh mango and spices', 'Beverages', 'img/Mango-Lassi-Recipe-Card-1.webp'),
('Egg Hoppers', 650.00, 'Traditional Sri Lankan bowl-shaped pancakes with egg and sambols', 'Local', 'img/23A8360.jpg');