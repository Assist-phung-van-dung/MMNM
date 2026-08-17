# Soketi cho Thiền Đường

WordPress **không tự chạy Soketi**. Thư mục này chỉ là mẫu deploy một instance Soketi; có thể dùng Docker, systemd hoặc hạ tầng hiện có.

**Hai file compose, đừng nhầm:**
- `docker-compose.example.yml` — bản MẪU, để `CHANGE_ME_APP_KEY`/`CHANGE_ME_APP_SECRET`, được commit lên git để tham khảo.
- `docker-compose.yml` — bản THẬT, đã có sẵn APP_KEY/APP_SECRET sinh ngẫu nhiên, **bị `.gitignore` loại khỏi git** (cùng nguyên tắc với `.env`/`wp-config.php`) vì đây là bí mật thật. Chạy trực tiếp file này, không cần đổi gì thêm — chỉ cần copy đúng App key/secret sang ô cài đặt WordPress ở bước 4.

1. (Chỉ cần nếu muốn tự sinh secret khác) Đổi `CHANGE_ME_APP_KEY`/`CHANGE_ME_APP_SECRET` trong `docker-compose.yml` (không phải bản `.example`).
2. Chạy Soketi, mặc định bind nội bộ port `6001`.
3. Reverse proxy một domain TLS, ví dụ `socket.nntm.com`, theo `nginx.example.conf`.
4. Trong WordPress vào **Nhạc Thiền -> Realtime Thiền Đường**:
   - App key: giống `SOKETI_DEFAULT_APP_KEY`
   - App secret: giống `SOKETI_DEFAULT_APP_SECRET`
   - WebSocket host: `socket.nntm.com`
   - WSS port: `443`
   - Bật TLS và Bật realtime
5. Mở Thiền Đường bằng hai tài khoản/trình duyệt để test.

## Kênh

- `presence-nntm-thien-duong`: tổng user đăng nhập đang ở trang.
- `presence-nntm-thien-duong-track-{POST_ID}`: user đang thực sự `playing` bài đó.

`App secret` chỉ tồn tại server-side WordPress/Soketi và không được đưa ra frontend.
