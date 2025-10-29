document.addEventListener("DOMContentLoaded", function() {

    // 1. หา Element ที่เราต้องใช้บ่อยๆ
    const grandTotalElement = document.getElementById('cart-grand-total');

    // 2. วนลูปหา .cart_item (แทน .quantity-input)
    document.querySelectorAll('.cart_item').forEach(function(cartItem) {

        // 3. หาค่าต่างๆ จาก data- attributes ที่เราเพิ่มใน HTML
        const pid = cartItem.dataset.pid;
        const unitPrice = parseFloat(cartItem.dataset.unitPrice);

        // 4. หาปุ่ม/ช่องตัวเลข/ปุ่มลบ "ภายใน" cartItem นี้
        const numberInput = cartItem.querySelector('.qt-number');
        const minusBtn = cartItem.querySelector('.qt-minus');
        const plusBtn = cartItem.querySelector('.qt-plus');
        const removeBtn = cartItem.querySelector('.remove-btn');
        const lineTotalElement = cartItem.querySelector('.cart-item-line-total');

        // 5. เมื่อคลิกปุ่มบวก
        plusBtn.addEventListener('click', function() {
            let newQuantity = parseInt(numberInput.value) + 1;
            numberInput.value = newQuantity;
            sendCartUpdate(pid, newQuantity); // ส่งอัปเดตไปเบื้องหลัง
            updatePrices(); // อัปเดตราคาบนหน้าเว็บทันที
        });

        // 6. เมื่อคลิกปุ่มลบ
        minusBtn.addEventListener('click', function() {
            let newQuantity = parseInt(numberInput.value) - 1;

            if (newQuantity >= 0) {
                numberInput.value = newQuantity;
                sendCartUpdate(pid, newQuantity); // ส่งอัปเดต
                updatePrices(); // อัปเดตราคา
            }

            if (newQuantity === 0) {
                // ถ้าเหลือ 0, ให้ลบแถวนั้นทิ้ง
                cartItem.remove();
            }
        });

        // 7. เมื่อคลิกปุ่มถังขยะ (ลบ)
        removeBtn.addEventListener('click', function() {
            sendCartUpdate(pid, 0); // ส่งค่า 0 (ลบ) ไปเบื้องหลัง
            cartItem.remove(); // ลบแถวนี้ทิ้งทันที
            updatePrices(); // อัปเดตราคา
        });
    });

    // 8. (แก้ไข) ฟังก์ชันสำหรับ "คำนวณและอัปเดต" ยอดรวมทั้งหมด
    function updatePrices() {
        // (ใหม่) เพิ่มตัวแปรสำหรับราคารวม (เต็ม)
        let newGrandTotal_Final = 0;
        let newGrandTotal_Original = 0;

        // (ใหม่) หา Element ของแถวสรุปผลทั้งหมด
        const originalTotalElement = document.getElementById('cart-original-total');
        const discountTotalElement = document.getElementById('cart-discount-total');
        const finalTotalElement = document.getElementById('cart-grand-total');

        // วนลูป .cart_item "ทั้งหมด" ที่ยังเหลืออยู่
        document.querySelectorAll('.cart_item').forEach(function(item) {

            // (แก้ไข) อ่าน data attribute ทั้ง 2 ค่า
            const price_final = parseFloat(item.dataset.unitPriceFinal);
            const price_original = parseFloat(item.dataset.unitPriceOriginal);
            const quantity = parseInt(item.querySelector('.qt-number').value);

            // (แก้ไข) คำนวณราคารวม (หลังลด) ของแถว
            const lineTotal_Final = price_final * quantity;
            // (ใหม่) คำนวณราคารวม (เต็ม) ของแถว
            const lineTotal_Original = price_original * quantity;

            // (แก้ไข) อัปเดตราคารวมของ "แถว" นั้น
            item.querySelector('.cart-item-line-total').textContent = lineTotal_Final.toLocaleString() + ' บาท';

            // (แก้ไข) บวกยอดเข้ากับยอดรวมทั้งหมด
            newGrandTotal_Final += lineTotal_Final;
            newGrandTotal_Original += lineTotal_Original;
        });

        // (ใหม่) คำนวณส่วนลดรวม
        let newDiscountTotal = newGrandTotal_Original - newGrandTotal_Final;

        // (ใหม่) อัปเดต Element ทั้ง 3 ตัวในกล่องสรุป
        if (originalTotalElement) {
            originalTotalElement.textContent = newGrandTotal_Original.toLocaleString() + ' บาท';
        }
        if (discountTotalElement) {
            discountTotalElement.textContent = '- ' + newDiscountTotal.toLocaleString() + ' บาท';
        }
        if (finalTotalElement) {
            finalTotalElement.textContent = newGrandTotal_Final.toLocaleString() + ' บาท';
        }

        // (เดิม) ถ้าตะกร้าว่าง ให้ reload
        if (newGrandTotal_Final === 0 && document.querySelectorAll('.cart_item').length === 0) {
            window.location.reload();
        }
    }

    // 9. (เดิม) ฟังก์ชันสำหรับส่งข้อมูลไปอัปเดตตะกร้า (เบื้องหลัง)
    // (ฟังก์ชันนี้เหมือนเดิมเป๊ะ ไม่ต้องแก้)
    function sendCartUpdate(pid, quantity) {
        console.log(`กำลังอัปเดต: PID=${pid}, จำนวน=${quantity}`);

        fetch('../cart/update_cart_quantity.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `pid=${pid}&quantity=${quantity}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('อัปเดตตะกร้าสำเร็จ!');
                } else {
                    console.error('อัปเดตล้มเหลว:', data.message);
                }
            })
            .catch(error => {
                console.error('เกิดข้อผิดพลาด:', error);
            });
    }
});