<?php

//cart class: handles the shopping cart stored in the session
class Cart
{
    // ====== INTERNAL SETUP ======

    //make sure the cart array exists in the session
    public static function init()
    {
        //if there is no cart yet, create an empty one
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    // ====== ADD / REMOVE / UPDATE LINES ======

    //add a product to the cart (or increase its quantity if it is already there)
    public static function add($product_id, $quantity = 1)
    {
        self::init(); //always be sure the cart is ready

        //if this product is already in the cart, just add to its quantity
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] += $quantity;
        } else {
            //if not in the cart yet, start it with this quantity
            $_SESSION['cart'][$product_id] = $quantity;
        }

        return true;
    }

    //remove a product completely from the cart
    public static function remove($product_id)
    {
        self::init();

        //only try to remove if the product actually exists in the cart
        if (isset($_SESSION['cart'][$product_id])) {
            unset($_SESSION['cart'][$product_id]);
            return true;
        }

        //if the product was not there, nothing changed
        return false;
    }

    //decrease the quantity of a product
    //if it goes to zero or below, we remove the product from the cart
    public static function decrease($product_id, $amount = 1)
    {
        self::init();

        //if the product is not in the cart, we cannot decrease it
        if (!isset($_SESSION['cart'][$product_id])) {
            return false;
        }

        //subtract the amount from the current quantity
        $_SESSION['cart'][$product_id] -= $amount;

        //if quantity is now zero or negative, remove the product line
        if ($_SESSION['cart'][$product_id] <= 0) {
            unset($_SESSION['cart'][$product_id]);
        }

        return true;
    }

    //set the exact quantity for a product
    public static function update($product_id, $quantity)
    {
        self::init();

        //if the new quantity is zero or less, treat it as a remove
        if ($quantity <= 0) {
            return self::remove($product_id);
        }

        //otherwise just store the new quantity
        $_SESSION['cart'][$product_id] = $quantity;
        return true;
    }

    // ====== READ CART STATE ======

    //get the full cart array in the format [product_id => quantity]
    public static function getItems()
    {
        self::init();
        return $_SESSION['cart'];
    }

    //get the total number of items in the cart (sum of all quantities)
    public static function getCount()
    {
        self::init();
        return array_sum($_SESSION['cart']);
    }

    // ====== PRICING HELPERS ======

    //calculate the total price for everything in the cart
    //expects a pdo connection so it can look up prices
    public static function getTotal($db)
    {
        self::init();
        $total = 0;

        //loop through each product in the cart
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            //look up the product price in the database
            $query = "SELECT price FROM products WHERE id = :id LIMIT 1";
            $stmt  = $db->prepare($query);
            $stmt->bindParam(':id', $product_id, PDO::PARAM_INT);
            $stmt->execute();

            //if the product exists, multiply its price by the quantity
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $total += $row['price'] * $quantity;
            }
        }

        //return the final sum for the whole cart
        return $total;
    }

    // ====== CLEAR CART ======

    //remove all items from the cart
    public static function clear()
    {
        //reset the cart back to an empty array
        $_SESSION['cart'] = [];
    }
}
?>
