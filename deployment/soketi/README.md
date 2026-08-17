# Soketi cho Thiền Đường

WordPress **không tự chạy Soketi**. Thư mục này chỉ là mẫu deploy một instance Soketi; có thể dùng Docker, systemd hoặc hạ tầng hiện có.

1. Đổi `CHANGE_ME_APP_KEY` và `CHANGE_ME_APP_SECRET` trong `docker-compose.example.yml`.
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
