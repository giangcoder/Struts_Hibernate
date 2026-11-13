package vn.hosoweb.service;

import org.hibernate.Session;
import vn.hosoweb.model.*;

import java.util.List;

/** Các truy vấn dùng chung, luôn giới hạn theo chủ sở hữu. */
public final class Repo {
    private Repo() { }

    public static User userByEmail(Session s, String email) {
        return s.createQuery("from User where lower(email) = :e", User.class)
                .setParameter("e", email.trim().toLowerCase()).uniqueResult();
    }

    public static List<Project> projects(Session s, long ownerId) {
        return s.createQuery("from Project where owner.id = :o order by updatedAt desc", Project.class)
                .setParameter("o", ownerId).list();
    }

    public static Project project(Session s, long ownerId, Long id) {
        if (id == null) return null;
        return s.createQuery("from Project where id = :id and owner.id = :o", Project.class)
                .setParameter("id", id).setParameter("o", ownerId).uniqueResult();
    }

    public static List<Platform> platforms(Session s, long projectId) {
        return s.createQuery("from Platform where project.id = :p order by position, id", Platform.class)
                .setParameter("p", projectId).list();
    }

    public static Platform platform(Session s, long ownerId, Long id) {
        if (id == null) return null;
        return s.createQuery("select pl from Platform pl join fetch pl.project pr where pl.id = :id and pr.owner.id = :o", Platform.class)
                .setParameter("id", id).setParameter("o", ownerId).uniqueResult();
    }

    public static List<SocialLink> socialLinks(Session s, long projectId) {
        return s.createQuery("from SocialLink where project.id = :p order by id", SocialLink.class)
                .setParameter("p", projectId).list();
    }

    public static List<Platform> completed(Session s, long projectId) {
        return s.createQuery("from Platform where project.id = :p and status = :st order by completedAt desc, id", Platform.class)
                .setParameter("p", projectId).setParameter("st", Platform.ST_DONE).list();
    }

    /** URL hoàn tất chưa gửi Sinbyte thành công (gồm cả lượt lỗi trước đó). */
    public static List<Platform> unsentCompleted(Session s, long projectId, int limit) {
        return s.createQuery("from Platform where project.id = :p and status = :st and profileUrl is not null "
                        + "and (sinbyteStatus is null or sinbyteStatus <> :sent) order by completedAt, id", Platform.class)
                .setParameter("p", projectId).setParameter("st", Platform.ST_DONE).setParameter("sent", "Đã gửi Sinbyte")
                .setMaxResults(limit).list();
    }

    public static long countByStatus(Session s, long projectId, String status) {
        return s.createQuery("select count(*) from Platform where project.id = :p and status = :st", Long.class)
                .setParameter("p", projectId).setParameter("st", status).uniqueResult();
    }

    public static int nextPosition(Session s, long projectId) {
        Integer m = s.createQuery("select max(position) from Platform where project.id = :p", Integer.class)
                .setParameter("p", projectId).uniqueResult();
        return m == null ? 0 : m + 1;
    }
}
